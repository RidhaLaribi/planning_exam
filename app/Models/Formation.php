<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'dept_id', 'nb_modules'];

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'dept_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function etudiants()
    {
        return $this->hasMany(Etudiant::class);
    }
}
