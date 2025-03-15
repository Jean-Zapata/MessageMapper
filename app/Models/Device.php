<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    // Especifica los campos que pueden ser asignados en masa
    protected $fillable = [
        'name',
        'phone_number',
        'status',
    ];
}
