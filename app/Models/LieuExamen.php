<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LieuExamen extends Model
{
    use HasFactory;

    protected $table = 'lieu_examen'; // Explicit table name to match migration

    protected $fillable = ['nom', 'capacite', 'type', 'batiment'];

    public function examens()
    {
        return $this->hasMany(Examen::class, 'salle_id');
    }
}
