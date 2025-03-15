<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    // Desactivar timestamps automáticos
    public $timestamps = false;

    // Los campos permitidos para asignación masiva
    protected $fillable = ['name', 'phone_number'];

    // Relación con las campañas
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_contact');
    }

    // Relación con las etiquetas
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }
}
