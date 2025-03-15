<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'campaign_id',
        'sent_at',
        'status',
    ];

    public $timestamps = false;
}
