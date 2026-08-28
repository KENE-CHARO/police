<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Historique;

class Plainte extends Model
{
    use HasFactory;

    protected $fillable = ['reference','plaignant_id','commissariat_id','titre','description','statut'];

    public function plaignant()
    {
        return $this->belongsTo(User::class, 'plaignant_id');
    }

    public function commissariat()
    {
        return $this->belongsTo(Commissariat::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function historiques()
    {
        return $this->morphMany(Historique::class, 'subject');
    }

    public function enquetes()
    {
        return $this->hasMany(Enquete::class, 'plainte_id');
    }
}
