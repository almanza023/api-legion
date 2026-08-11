<?php

namespace App\Http\Controllers;

use App\Models\ClienteMembresia;
use App\Models\SmsConfiguracion;
use App\Models\SmsEnvioLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NoitificacioControoller extends Controller
{
    public function enviarSmsMembresiasPorVencer()
    {
        $fechaBase = Carbon::today();

        $configuraciones = SmsConfiguracion::query()
            ->where('estado', 'activo')
            ->get();

        if ($configuraciones->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'No hay configuraciones de SMS activas para procesar.',
            ], 422);
        }

        $resumen = [
            'fecha_base' => $fechaBase->toDateString(),
            'procesados' => 0,
            'enviados' => 0,
            'fallidos' => 0,
            'sin_telefono' => 0,
            'detalles' => [],
        ];

        foreach ($configuraciones as $configuracion) {
            $fechaObjetivo = $fechaBase->copy()->addDays((int) $configuracion->dias)->toDateString();

            $membresias = ClienteMembresia::query()
                ->with('cliente:id,telefono,nombre,apellidos')
                ->where('estado', 'activo')
                ->whereDate('fecha_fin', $fechaObjetivo)
                ->get();

            foreach ($membresias as $membresia) {
                $resumen['procesados']++;
                $telefono = $this->normalizarTelefonoColombia((string) data_get($membresia, 'cliente.telefono', ''));

                if ($telefono === null) {
                    $resumen['fallidos']++;
                    $resumen['sin_telefono']++;

                    $log = SmsEnvioLog::create([
                        'telefono' => trim((string) data_get($membresia, 'cliente.telefono', '')) ?: 'SIN_TELEFONO',
                        'mensaje' => $configuracion->mensaje,
                        'sms_configuracion_id' => $configuracion->id,
                        'request_payload' => [
                            'cliente_id' => $membresia->cliente_id,
                            'membresia_id' => $membresia->id,
                            'fecha_fin' => optional($membresia->fecha_fin)->toDateString(),
                        ],
                        'se_envio' => false,
                        'error' => 'Cliente sin telefono valido de 10 digitos',
                    ]);

                    $resumen['detalles'][] = [
                        'cliente_id' => $membresia->cliente_id,
                        'membresia_id' => $membresia->id,
                        'telefono' => null,
                        'se_envio' => false,
                        'log_id' => $log->id,
                        'error' => 'Cliente sin telefono valido de 10 digitos',
                    ];

                    continue;
                }

                $resultado = $this->enviarSmsProveedor(
                    $telefono,
                    $configuracion->mensaje,
                    $configuracion->id,
                    [
                        'costCenter' => 0,
                        'reference01' => 'cliente:' . $membresia->cliente_id,
                        'reference02' => 'membresia:' . $membresia->id,
                        'reference03' => 'vencimiento',
                    ]
                );

                if ($resultado['se_envio']) {
                    $resumen['enviados']++;
                } else {
                    $resumen['fallidos']++;
                }

                $resumen['detalles'][] = [
                    'cliente_id' => $membresia->cliente_id,
                    'membresia_id' => $membresia->id,
                    'telefono' => $telefono,
                    'se_envio' => $resultado['se_envio'],
                    'log_id' => $resultado['log_id'],
                    'provider_status_code' => $resultado['provider_status_code'],
                    'provider_status_message' => $resultado['provider_status_message'],
                ];
            }
        }

        return response()->json([
            'status' => 'ok',
            'msg' => 'Proceso de notificación de membresías finalizado',
            'data' => $resumen,
        ]);
    }

    public function historial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telefono' => 'nullable|string|max:30',
            'se_envio' => 'nullable|in:true,false,0,1',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $query = SmsEnvioLog::query()->with('smsConfiguracion:id,dias,mensaje,estado');

        if ($request->filled('telefono')) {
            $query->where('telefono', 'like', '%' . $request->telefono . '%');
        }

        if ($request->filled('se_envio')) {
            $seEnvio = filter_var($request->se_envio, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($seEnvio !== null) {
                $query->where('se_envio', $seEnvio);
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $perPage = (int) $request->get('per_page', 15);
        $logs = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'status' => 'ok',
            'msg' => 'Operación exitosa',
            'data' => $logs->items(),
            'current_page' => $logs->currentPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
            'last_page' => $logs->lastPage(),
        ]);
    }

    public function enviarSms(Request $request)
    {
        $telefonoNormalizado = $this->normalizarTelefonoColombia((string) $request->telefono);

        $validator = Validator::make(array_merge($request->all(), [
            'telefono' => $telefonoNormalizado,
        ]), [
            'telefono' => ['required', 'regex:/^[0-9]{10}$/'],
            'mensaje' => 'nullable|string',
            'sms_configuracion_id' => 'nullable|integer|exists:sms_configuraciones,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'El telefono debe tener exactamente 10 digitos.',
            ], 422);
        }

        $telefono = $telefonoNormalizado;

        $mensaje = $request->input('mensaje');

        $configuracion = null;
        if (!$mensaje) {
            if ($request->filled('sms_configuracion_id')) {
                $configuracion = SmsConfiguracion::where('estado', 'activo')
                    ->find($request->sms_configuracion_id);
            }

            if (!$configuracion) {
                $configuracion = SmsConfiguracion::where('estado', 'activo')
                    ->orderByDesc('id')
                    ->first();
            }

            $mensaje = $configuracion?->mensaje;
        }

        if (!$mensaje) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'No existe mensaje para enviar. Configure SMS activo o envíe el campo mensaje.',
            ], 422);
        }

        $resultado = $this->enviarSmsProveedor(
            $telefono,
            $mensaje,
            $configuracion?->id,
            [
                'costCenter' => 0,
                'reference01' => 'api-gym',
                'reference02' => 'manual',
                'reference03' => 'sms',
            ]
        );

        return response()->json([
            'status' => $resultado['se_envio'] ? 'ok' : 'fail',
            'msg' => $resultado['se_envio'] ? 'SMS enviado correctamente' : 'No se pudo enviar el SMS',
            'data' => [
                'telefono' => $telefono,
                'mensaje' => $mensaje,
                'se_envio' => $resultado['se_envio'],
                'http_status' => $resultado['http_status'],
                'provider_status_code' => $resultado['provider_status_code'],
                'provider_status_message' => $resultado['provider_status_message'],
                'provider_message_id' => $resultado['provider_message_id'],
                'provider_message_status_id' => $resultado['provider_message_status_id'],
                'log_id' => $resultado['log_id'],
            ],
        ], $resultado['se_envio'] ? 200 : $resultado['http_status_code_api']);
    }

    private function enviarSmsProveedor(string $telefono, string $mensaje, ?int $smsConfiguracionId, array $messageMeta = []): array
    {
        $apiKey = config('services.hablame.api_key') ?: env('HABLAME_API_KEY');
        $telefonoProveedor = '57' . $telefono;

        $payload = [
            'priority' => true,
            'certificate' => true,
            'sendDate' => 'Now',
            'campaignName' => config('services.hablame.campaign_name'),
            'from' => config('services.hablame.from'),
            'flash' => true,
            'messages' => [
                array_merge([
                    'to' => $telefonoProveedor,
                    'text' => $mensaje,
                ], $messageMeta),
            ],
        ];

        $log = SmsEnvioLog::create([
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'sms_configuracion_id' => $smsConfiguracionId,
            'request_payload' => $payload,
            'se_envio' => false,
        ]);

        try {
            $headers = [
                'X-Hablame-Key' => $apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->post(config('services.hablame.url'), $payload);

            $responseData = $response->json();
            $providerStatusCode = data_get($responseData, 'statusCode');
            $providerStatusMessage = data_get($responseData, 'statusMessage');
            $providerMessageId = data_get($responseData, 'payLoad.messages.0.id');
            $providerMessageStatusId = data_get($responseData, 'payLoad.messages.0.statusId');

            $ok = $response->successful()
                && (int) $providerStatusCode === 200
                && !empty($providerMessageId);

            $log->update([
                'response_payload' => $responseData ?? ['raw' => $response->body()],
                'http_status' => $response->status(),
                'se_envio' => $ok,
                'error' => $ok ? null : ($providerStatusMessage ?: $response->body()),
            ]);

            return [
                'se_envio' => $ok,
                'http_status' => $response->status(),
                'provider_status_code' => $providerStatusCode,
                'provider_status_message' => $providerStatusMessage,
                'provider_message_id' => $providerMessageId,
                'provider_message_status_id' => $providerMessageStatusId,
                'log_id' => $log->id,
                'http_status_code_api' => $ok ? 200 : 502,
            ];
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());

            $log->update([
                'se_envio' => false,
                'error' => $ex->getMessage(),
            ]);

            return [
                'se_envio' => false,
                'http_status' => null,
                'provider_status_code' => null,
                'provider_status_message' => $ex->getMessage(),
                'provider_message_id' => null,
                'provider_message_status_id' => null,
                'log_id' => $log->id,
                'http_status_code_api' => 500,
            ];
        }
    }

    private function normalizarTelefonoColombia(string $telefono): ?string
    {
        $digits = preg_replace('/\D+/', '', $telefono ?? '');

        if (str_starts_with($digits, '57') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10) {
            return null;
        }

        return $digits;
    }
}
