<?php

namespace App\Services;

use App\Models\Examen;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExamViewService
{
    /**
     * Get exam schedule for a specific student.
     * Uses deterministic logic to calculate room assignment.
     */
    public function getStudentSchedule($studentId)
    {
        // 1. Get Student's enrolled modules
        // Inscriptions are the source of truth for enrollment
        $moduleIds = Inscription::where('etudiant_id', $studentId)
            ->pluck('module_id')
            ->toArray();

        if (empty($moduleIds)) {
            return [];
        }

        $myExams = [];

        // 2. Iterate through each module to find the assigned room
        foreach ($moduleIds as $moduleId) {
            // Fetch all exam rooms for this module, sorted deterministically
            $exams = Examen::where('module_id', $moduleId)
                ->with(['salle', 'module', 'prof'])
                ->orderBy('id') // Vital: Must be stable
                ->get();

            if ($exams->isEmpty()) {
                continue;
            }

            // Fetch all students enrolled in this module, sorted deterministically
            // Optimization: We only need IDs, but sorting must be identical to what logic dictated
            // The logic: Students are distributed by ID ascending.
            $enrolledStudentIds = Inscription::where('module_id', $moduleId)
                ->orderBy('etudiant_id', 'asc')
                ->pluck('etudiant_id')
                ->toArray();

            // 3. Deterministic Assignment Algorithm
            $offset = 0;
            $assignedExam = null;

            foreach ($exams as $exam) {
                // Determine capacity for this subset
                // Ideally, we stored this distribution logic somewhere, but usually it's based on Room Capacity 
                // adjusted for how many students actually exist vs total capacity.
                // However, the simplest deterministic rule used in generation was "Fill Room 1, then Room 2..."
                $capacity = $exam->salle->capacite;

                // Get the slice of students for this room
                // We don't effectively slice the array for performance, just check indices
                // Range for this room: [offset, offset + capacity - 1]

                $myIndex = array_search($studentId, $enrolledStudentIds);

                if ($myIndex !== false && $myIndex >= $offset && $myIndex < ($offset + $capacity)) {
                    $assignedExam = $exam;
                    break;
                }

                $offset += $capacity;
            }

            if ($assignedExam) {
                $myExams[] = $assignedExam;
            }
        }

        return $myExams;
    }

    /**
     * Get exam schedule for a professor.
     */
    public function getProfessorSchedule($profId)
    {
        return Examen::where('prof_id', $profId)
            ->with(['module', 'salle'])
            ->orderBy('date_heure')
            ->get();
    }

    /**
     * Get all exams for admin view.
     */
    public function getAdminSchedule()
    {
        return Examen::with(['module', 'salle', 'prof'])
            ->orderBy('date_heure')
            ->get();
    }
}
