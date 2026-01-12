<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnscheduledExam extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['module_id', 'nom', 'formation_id', 'reason'];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }
}
