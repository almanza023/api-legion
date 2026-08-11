<?php

namespace App\Http\Controllers;

use App\Models\Publicidad;
use App\Models\PublicidadDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicidadAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $publicidades = Publicidad::with('detalles')->orderBy('fecha_inicio', 'desc')->paginate($perPage);
                
                return response()->json([
                    'data' => $publicidades->items(),
                    'current_page' => $publicidades->currentPage(),
                    'per_page' => $publicidades->perPage(),
                    'total' => $publicidades->total(),
                    'last_page' => $publicidades->lastPage()
                ]);
            } else {
                $publicidades = Publicidad::with('detalles')->orderBy('fecha_inicio', 'desc')->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $publicidades]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function vigentes(){
        try {
            $publicidades = Publicidad::vigentes()->with('detalles')->orderBy('fecha_inicio', 'desc')->get();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $publicidades]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            $request->validate([
                'titulo' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            $publicidad = new Publicidad();
            $publicidad->titulo = $request->titulo;
            $publicidad->descripcion = $request->descripcion;
            $publicidad->fecha_inicio = $request->fecha_inicio;
            $publicidad->fecha_fin = $request->fecha_fin;
            $publicidad->estado = $request->estado ?? 'activo';
            $publicidad->save();

            $detalles = [];
            $orden = 0;

            if ($request->hasFile('imagenes')) {
                $imagenes = $request->file('imagenes');
                Log::info('Procesando ' . count($imagenes) . ' imágenes');
                
                foreach ($imagenes as $imagen) {
                    try {
                        $path = $imagen->store('publicidad/imagenes', 'public');
                        
                        $detalles[] = [
                            'publicidad_id' => $publicidad->id,
                            'tipo' => 'imagen',
                            'ruta' => $path,
                            'orden' => $orden++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error al guardar imagen: ' . $e->getMessage());
                        throw $e;
                    }
                }
            }

            if ($request->hasFile('videos')) {
                $videos = $request->file('videos');
                Log::info('Procesando ' . count($videos) . ' videos');
                
                foreach ($videos as $video) {
                    try {
                        $path = $video->store('publicidad/videos', 'public');
                        
                        $detalles[] = [
                            'publicidad_id' => $publicidad->id,
                            'tipo' => 'video',
                            'ruta' => $path,
                            'orden' => $orden++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error al guardar video: ' . $e->getMessage());
                        throw $e;
                    }
                }
            }

            if (!empty($detalles)) {
                DB::table('publicidad_detalle')->insert($detalles);
            }

            $publicidad->load('detalles');

            DB::commit();
            return response()->json([
                'status' => 'ok', 
                'msg' => 'Publicidad creada exitosamente', 
                'data' => $publicidad,
                'total_archivos' => count($detalles)
            ]);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            DB::rollBack();
            return response()->json(['status' => 'fail', 'msg' => 'Error de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $publicidad = Publicidad::with('detalles')->find($id);
            if(!$publicidad){
                return response()->json(['status' => 'fail', 'msg' => 'Publicidad no encontrada', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $publicidad]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        DB::beginTransaction();
        try {
            $publicidad = Publicidad::with('detalles')->find($id);
            if(!$publicidad){
                return response()->json(['status' => 'fail', 'msg' => 'Publicidad no encontrada', 'data' => null], 404);
            }

            $request->validate([
                'titulo' => 'sometimes|required|string|max:255',
                'fecha_inicio' => 'sometimes|required|date',
                'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            ]);

            if($request->has('titulo')) $publicidad->titulo = $request->titulo;
            if($request->has('descripcion')) $publicidad->descripcion = $request->descripcion;
            if($request->has('fecha_inicio')) $publicidad->fecha_inicio = $request->fecha_inicio;
            if($request->has('fecha_fin')) $publicidad->fecha_fin = $request->fecha_fin;
            if($request->has('estado')) $publicidad->estado = $request->estado;

            if ($request->hasFile('imagenes') || $request->hasFile('videos')) {
                Log::info('Eliminando archivos anteriores de publicidad ID: ' . $publicidad->id);
                
                foreach ($publicidad->detalles as $detalle) {
                    Storage::disk('public')->delete($detalle->ruta);
                }
                
                DB::table('publicidad_detalle')->where('publicidad_id', $publicidad->id)->delete();

                $detalles = [];
                $orden = 0;

                if ($request->hasFile('imagenes')) {
                    $imagenes = $request->file('imagenes');
                    Log::info('Procesando ' . count($imagenes) . ' nuevas imágenes');
                    
                    foreach ($imagenes as $imagen) {
                        try {
                            $path = $imagen->store('publicidad/imagenes', 'public');
                            
                            $detalles[] = [
                                'publicidad_id' => $publicidad->id,
                                'tipo' => 'imagen',
                                'ruta' => $path,
                                'orden' => $orden++,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error al guardar imagen: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                if ($request->hasFile('videos')) {
                    $videos = $request->file('videos');
                    Log::info('Procesando ' . count($videos) . ' nuevos videos');
                    
                    foreach ($videos as $video) {
                        try {
                            $path = $video->store('publicidad/videos', 'public');
                            
                            $detalles[] = [
                                'publicidad_id' => $publicidad->id,
                                'tipo' => 'video',
                                'ruta' => $path,
                                'orden' => $orden++,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error al guardar video: ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                if (!empty($detalles)) {
                    DB::table('publicidad_detalle')->insert($detalles);
                }
            }

            $publicidad->save();
            $publicidad->load('detalles');

            DB::commit();
            return response()->json([
                'status' => 'ok', 
                'msg' => 'Publicidad actualizada exitosamente', 
                'data' => $publicidad,
                'total_archivos' => $publicidad->detalles->count()
            ]);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            DB::rollBack();
            return response()->json(['status' => 'fail', 'msg' => 'Error de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        DB::beginTransaction();
        try {
            $publicidad = Publicidad::with('detalles')->find($id);
            if(!$publicidad){
                return response()->json(['status' => 'fail', 'msg' => 'Publicidad no encontrada', 'data' => null], 404);
            }

            foreach ($publicidad->detalles as $detalle) {
                Storage::disk('public')->delete($detalle->ruta);
            }

            $publicidad->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'msg' => 'Publicidad eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

}
