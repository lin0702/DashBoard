<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Aloha extends Model
{
    use HasFactory;

    protected $fillable = [
        'Aloha_Id', 
        'KML', 
        'Summary', 
        'Wind_Speed',
        'Wind_Direction',
        'Longitude',
        'Latitude',
        'CreaterTime'
    ];

    protected $primaryKey = 'Aloha_Id';
    protected $table = 'Aloha_event';
    public $timestamps = false;
}
