<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use SoftDeletes;

    protected $table = "videos";
    protected $fillable = [
        'nombre_archivo',
        'url_video',
        'formato',
        'duracion_seg',
        'tamano_mb',
        'uploaded_by',
        'estado',
        'is_file'
    ];

    protected $appends = ['url_temporal'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Genera una URL temporal firmada válida por 60 minutos
     * Si es una URL externa, la devuelve directamente
     */
    public function getUrlTemporalAttribute()
    {
        // Si es una URL externa (http/https), devolverla directamente
        if (filter_var($this->url_video, FILTER_VALIDATE_URL)) {
            return $this->url_video;
        }
        
        // Si es un archivo local, generar URL temporal
        if ($this->url_video && Storage::exists($this->url_video)) {
            return Storage::temporaryUrl(
                $this->url_video,
                now()->addMinutes(60)
            );
        }
        
        return null;
    }
}
