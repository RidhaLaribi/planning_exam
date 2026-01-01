<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professeur extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'dept_id', 'specialite', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'dept_id');
    }

    public function examens()
    {
        return $this->hasMany(Examen::class, 'prof_id');
    }
}
