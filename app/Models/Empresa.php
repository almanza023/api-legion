<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $fillable = [
        'nombre',
        'nit',
        'direccion',
        'correo',
        'telefono',
        'representante',
        'logo',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        if (str_starts_with($this->logo, '/storage')) {
            return asset(ltrim($this->logo, '/'));
        }

        if (str_starts_with($this->logo, 'storage/')) {
            return asset($this->logo);
        }

        return Storage::url($this->logo);
    }
}
