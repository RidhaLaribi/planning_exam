<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'prof_id', 'salle_id', 'date_heure', 'duree_minutes'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function professeur()
    {
        return $this->belongsTo(Professeur::class, 'prof_id');
    }

    public function lieuExamen()
    {
        return $this->belongsTo(LieuExamen::class, 'salle_id');
    }
}
