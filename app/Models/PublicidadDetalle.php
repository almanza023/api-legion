<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PublicidadDetalle extends Model
{
    use HasFactory;

    protected $table = 'publicidad_detalle';

    protected $fillable = [
        'publicidad_id',
        'tipo',
        'ruta',
        'orden',
    ];

    protected $hidden = ['ruta'];

    protected $appends = ['imagen_base64'];

    public function publicidad()
    {
        return $this->belongsTo(Publicidad::class, 'publicidad_id');
    }

    public function getImagenBase64Attribute()
    {
        if (!$this->ruta) {
            return null;
        }

        if (filter_var($this->ruta, FILTER_VALIDATE_URL)) {
            return $this->ruta;
        }

        if (Storage::disk('public')->exists($this->ruta)) {
            try {
                $file = Storage::disk('public')->get($this->ruta);
                $path = Storage::disk('public')->path($this->ruta);
                $mimeType = mime_content_type($path);
                
                return 'data:' . $mimeType . ';base64,' . base64_encode($file);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
