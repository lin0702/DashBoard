<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Weather extends Model
{
    use HasFactory;

    protected $fillable = [
        'Weather_Id',
        'Weather_data',
        'CreateTime'
    ];

    protected $primaryKey = 'Weather_Id';
    protected $table = 'Weather_event';
}
