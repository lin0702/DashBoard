<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Factory extends Model
{
    use HasFactory;

    protected $fillable = [
        'Location_Id', 
        'Location_Name', 
        'Control_Id', 
        'Coordinate',
        'Contact_Person',
        'Factory_Map',
        'Equipment_Diagram',
        'Chemicals',
        'Dangerous',
        'Picture',
        'CreateTime'
    ];

    protected $primaryKey = 'Location_Id';
    protected $table = 'Factory_Area';
    public $timestamps = false;
}
