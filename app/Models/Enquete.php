<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquete extends Model
{
    use HasFactory;

    protected $fillable = ['plainte_id','enqueteur_id','rapport','statut'];

    public function plainte()
    {
        return $this->belongsTo(Plainte::class, 'plainte_id');
    }

    public function enqueteur()
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
