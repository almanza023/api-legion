<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\IsUserAuth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(Cors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $status = 500;
            $msg = 'Ha ocurrido un error al procesar la solicitud';
            $errors = null;

            if ($e instanceof ValidationException) {
                $status = 422;
                $msg = 'Errores de validacion';
                $errors = $e->errors();
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
                $msg = 'No autenticado';
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $msg = $e->getMessage() ?: match ($status) {
                    403 => 'No autorizado para realizar esta accion',
                    404 => 'Recurso no encontrado',
                    405 => 'Metodo no permitido',
                    419 => 'Sesion expirada',
                    429 => 'Demasiadas solicitudes',
                    default => 'Error HTTP',
                };
            }

            $payload = [
                'status' => 'fail',
                'msg' => $msg,
                'code' => $status,
                'path' => $request->path(),
                'timestamp' => now()->toISOString(),
            ];

            if (!is_null($errors)) {
                $payload['errors'] = $errors;
            }

            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
                $payload['detail'] = $e->getMessage();
            }

            return response()->json($payload, $status);
        });
    })->create();
