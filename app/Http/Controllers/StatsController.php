<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Etudiant;
use App\Models\Examen;
use App\Models\LieuExamen;
use App\Services\ExamSchedulerService;

class StatsController extends Controller
{
    public function index()
    {
        // Use count() for pure counts which is much faster than get()
        return response()->json([
            'data' => [
                'totalStudents' => Etudiant::count(),
                'totalExams' => Examen::count(),
                'totalRooms' => LieuExamen::count(),
                'conflicts' => 0 // Calculating conflicts is heavy, maybe skip or cache?
            ]
        ]);
    }
}
