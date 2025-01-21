<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Sensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'Factory_Id', 
        'Factory_Code', 
        'CEMS_Code', 
        'Flare_Code'
    ];

    protected $primaryKey = 'Factory_Id';
    protected $table = 'Senser';
}
