<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoAsynController extends Controller
{
    public function index(){
        try {
            $videos = Video::with('usuario')->get();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $videos]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $video = new Video();
            
            // Caso 1: Se envía un archivo de video
            if ($request->hasFile('video')) {
                $video_file = $request->file('video');
                
                // Validar que sea un video
                $allowed_mimes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi'];
                if (!in_array($video_file->getMimeType(), $allowed_mimes)) {
                    return response()->json(['status' => 'fail', 'msg' => 'El archivo debe ser un video válido', 'data' => null], 400);
                }

                // Generar nombre único
                $extension = $video_file->getClientOriginalExtension();
                $nombre_archivo = Str::slug(pathinfo($video_file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                
                // Guardar archivo en storage/app/videos
                $path = $video_file->storeAs('videos', $nombre_archivo, 'local');

                // Calcular tamaño en MB
                $tamano_mb = round($video_file->getSize() / 1048576, 2);

                $video->nombre_archivo = $request->nombre_archivo ?? $video_file->getClientOriginalName();
                $video->url_video = $path;
                $video->formato = $extension;
                $video->tamano_mb = $tamano_mb;
                $video->is_file = 1; // Marcar como archivo
            }
            // Caso 2: Se envía una URL de video
            elseif ($request->has('url_video')) {
                $video->nombre_archivo = $request->nombre_archivo ?? 'Video desde URL';
                $video->url_video = $request->url_video;
                $video->formato = $request->formato ?? 'url';
                $video->tamano_mb = $request->tamano_mb ?? null;
                $video->is_file = 0; // Marcar como URL
            }
            else {
                return response()->json(['status' => 'fail', 'msg' => 'Debe enviar un archivo de video o una URL', 'data' => null], 400);
            }

            $video->duracion_seg = $request->duracion_seg;
            $video->uploaded_by = $request->user()->id ?? null;
            $video->estado = 'activo';
            $video->save();

            return response()->json(['status' => 'ok', 'msg' => 'Video guardado exitosamente', 'data' => $video]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $video = Video::with('usuario')->find($id);
            if(!$video){
                return response()->json(['status' => 'fail', 'msg' => 'Video no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $video]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $video = Video::find($id);
            if(!$video){
                return response()->json(['status' => 'fail', 'msg' => 'Video no encontrado', 'data' => null], 404);
            }

            // Si se envía un nuevo archivo de video
            if ($request->hasFile('video')) {
                $video_file = $request->file('video');
                
                // Eliminar archivo anterior solo si es un archivo local (is_file = 1)
                if ($video->is_file == 1 && Storage::exists($video->url_video)) {
                    Storage::delete($video->url_video);
                }

                // Guardar nuevo archivo
                $extension = $video_file->getClientOriginalExtension();
                $nombre_archivo = Str::slug(pathinfo($video_file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                $path = $video_file->storeAs('videos', $nombre_archivo, 'local');
                $tamano_mb = round($video_file->getSize() / 1048576, 2);

                $video->nombre_archivo = $video_file->getClientOriginalName();
                $video->url_video = $path;
                $video->formato = $extension;
                $video->tamano_mb = $tamano_mb;
                $video->is_file = 1; // Marcar como archivo
            }
            // Si se envía una URL de video
            elseif ($request->has('url_video')) {
                $video->url_video = $request->url_video;
                if($request->has('nombre_archivo')) $video->nombre_archivo = $request->nombre_archivo;
                if($request->has('formato')) $video->formato = $request->formato;
                if($request->has('tamano_mb')) $video->tamano_mb = $request->tamano_mb;
                $video->is_file = 0; // Marcar como URL
            }

            if($request->has('duracion_seg')) $video->duracion_seg = $request->duracion_seg;
            if($request->has('estado')) $video->estado = $request->estado;

            $video->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $video]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $video = Video::find($id);
            if(!$video){
                return response()->json(['status' => 'fail', 'msg' => 'Video no encontrado', 'data' => null], 404);
            }

            // Eliminar archivo físico solo si es un archivo local (is_file = 1)
            if ($video->is_file == 1 && Storage::exists($video->url_video)) {
                Storage::delete($video->url_video);
            }

            $video->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Video eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtiene la URL de reproducción del video según su tipo
     */
    public function getPlayUrl($id){
        try {
            $video = Video::find($id);
            if(!$video){
                return response()->json(['status' => 'fail', 'msg' => 'Video no encontrado', 'data' => null], 404);
            }

            $url_reproduccion = null;
            $tipo = null;

            // Si es un archivo local (is_file = 1), generar URL temporal
            if ($video->is_file == 1) {
                if (Storage::exists($video->url_video)) {
                    $url_reproduccion = Storage::temporaryUrl(
                        $video->url_video,
                        now()->addMinutes(60)
                    );
                    $tipo = 'archivo_local';
                } else {
                    return response()->json(['status' => 'fail', 'msg' => 'El archivo de video no existe', 'data' => null], 404);
                }
            }
            // Si es una URL externa (is_file = 0), devolverla directamente
            else {
                $url_reproduccion = $video->url_video;
                
                // Detectar tipo de URL
                if (strpos($url_reproduccion, 'youtube.com') !== false || strpos($url_reproduccion, 'youtu.be') !== false) {
                    $tipo = 'youtube';
                } elseif (strpos($url_reproduccion, 'vimeo.com') !== false) {
                    $tipo = 'vimeo';
                } else {
                    $tipo = 'url_externa';
                }
            }

            return response()->json([
                'status' => 'ok', 
                'msg' => 'URL de reproducción obtenida', 
                'data' => [
                    'url_video' => $url_reproduccion,
                    'tipo' => $tipo,
                    'is_file' => $video->is_file,
                    'expira_en' => $video->is_file == 1 ? '60 minutos' : null,
                    'video' => $video
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Genera una URL temporal para acceder al video y la actualiza en la base de datos
     * @deprecated Usar getPlayUrl en su lugar
     */
    public function getTemporaryUrl($id){
        try {
            $video = Video::find($id);
            if(!$video){
                return response()->json(['status' => 'fail', 'msg' => 'Video no encontrado', 'data' => null], 404);
            }

            $url_temporal = null;

            // Si es un archivo local (is_file = 1), generar URL temporal
            if ($video->is_file == 1 && Storage::exists($video->url_video)) {
                $url_temporal = Storage::temporaryUrl(
                    $video->url_video,
                    now()->addMinutes(60)
                );
            }
            // Si es una URL externa (is_file = 0), devolverla directamente
            else {
                $url_temporal = $video->url_video;
            }

            return response()->json([
                'status' => 'ok', 
                'msg' => 'URL temporal generada', 
                'data' => [
                    'url_temporal' => $url_temporal,
                    'expira_en' => '60 minutos',
                    'video' => $video
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
