<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Material extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'Material_Id', 
        'Material_Name', 
        'Hazard_Url'
    ];

    protected $primaryKey = 'Material_Id';
    protected $table = 'Toxic_Materials';
    public $timestamps = false;
}
