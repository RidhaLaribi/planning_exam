<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conflict extends Model
{
    protected $fillable = ['exam_id', 'conflict_with_exam_id', 'type', 'severity', 'description', 'resolved'];

    public function exam()
    {
        return $this->belongsTo(Examen::class, 'exam_id');
    }

    public function conflictWith()
    {
        return $this->belongsTo(Examen::class, 'conflict_with_exam_id');
    }
}
