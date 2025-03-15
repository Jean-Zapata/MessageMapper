<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Definir las columnas que se pueden asignar masivamente
    protected $fillable = ['name'];

    // Relación muchos a muchos con contacts
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_tag');
    }
}
