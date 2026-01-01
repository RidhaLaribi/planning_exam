<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;

    protected $fillable = ['nom'];

    public function formations()
    {
        return $this->hasMany(Formation::class, 'dept_id');
    }

    public function professeurs()
    {
        return $this->hasMany(Professeur::class, 'dept_id');
    }
}
