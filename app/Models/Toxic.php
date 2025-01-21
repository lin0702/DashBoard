<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Toxic extends Model
{
    protected $fillable = [
        'Toxic_Id', 
        'Tocic_Date', 
        'Tocic_Data', 
        'CreateTime'
    ];

    protected $primaryKey = 'Toxic_Id';
    protected $table = 'Toxic_Data';
    public $timestamps = false;
}
