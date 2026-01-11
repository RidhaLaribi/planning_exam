<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExamViewService;
use App\Models\Etudiant;
use App\Models\Professeur;
use Illuminate\Support\Facades\Log;

class ExamViewController extends Controller
{
    protected $service;

    public function __construct(ExamViewService $service)
    {
        $this->service = $service;
    }

    public function student(Request $request)
    {
        // Get Authenticated User ID (assume Auth middleware or pass user_id for dev)
        // For development without full Auth Auth:
        $userId = $request->user()->id ?? $request->query('user_id');

        if (!$userId) {
            // Fallback for demo if no auth: Pick first student
            $student = Etudiant::first();
        } else {
            $student = Etudiant::where('user_id', $userId)->first();
        }

        if (!$student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        $exams = $this->service->getStudentSchedule($student->id);
        return response()->json(['data' => $exams]);
    }

    public function professor(Request $request)
    {
        $userId = $request->user()->id ?? $request->query('user_id');

        if (!$userId) {
            // Fallback for demo
            $prof = Professeur::first();
        } else {
            $prof = Professeur::where('user_id', $userId)->first();
        }

        if (!$prof) {
            return response()->json(['message' => 'Professor profile not found'], 404);
        }

        $exams = $this->service->getProfessorSchedule($prof->id);
        return response()->json(['data' => $exams]);
    }

    public function admin()
    {
        $exams = $this->service->getAdminSchedule();
        return response()->json(['data' => $exams]);
    }
}
