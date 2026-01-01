<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'credits', 'formation_id', 'pre_req_id'];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function preRequis()
    {
        return $this->belongsTo(Module::class, 'pre_req_id');
    }

    public function examens()
    {
        return $this->hasMany(Examen::class);
    }

    public function etudiants()
    {
        return $this->belongsToMany(Etudiant::class, 'inscriptions')
                    ->withPivot('note')
                    ->withTimestamps();
    }
}
