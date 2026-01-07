<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamValidation extends Model
{
    protected $fillable = ['department_id', 'status', 'validated_by', 'comments'];

    //
}
