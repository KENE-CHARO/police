<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commissariat extends Model
{
    use HasFactory;

    protected $fillable = ['nom','adresse','telephone','db_name'];

    public function plaintes()
    {
        return $this->hasMany(Plainte::class);
    }
}
