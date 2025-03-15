<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'message',
        'device_id',
        'status',
        'scheduled_time'
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function sentMessages()
{
    return $this->hasMany(SentMessage::class);
}

}
