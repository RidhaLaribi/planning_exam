<?php

namespace App\Services;

use App\Models\Examen;
use App\Models\Conflict;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConflictDetectionService
{
    /**
     * Run all conflict checks and store them in database.
     */
    public function detectAll()
    {
        // Clear old unresolved conflicts? Or keep history? 
        // For this task, let's clear ephemeral conflicts or update them. 
        // Let's just create new ones for valid exams.

        $exams = Examen::with(['module.etudiants', 'professeur', 'lieuExamen'])->get();

        $conflicts = [];

        foreach ($exams as $exam) {
            // 1. Room Capacity
            $studentCount = $exam->module->etudiants->count();
            if ($exam->lieuExamen && $studentCount > $exam->lieuExamen->capacite) {
                $this->createConflict(
                    $exam->id,
                    null,
                    'room_capacity',
                    'high',
                    "Room {$exam->lieuExamen->nom} capacity ({$exam->lieuExamen->capacite}) exceeded by student count ({$studentCount})."
                );
            }

            // 2. Professor Overlap (Compare with other exams)
            // 3. Room Overlap
            foreach ($exams as $otherExam) {
                if ($exam->id >= $otherExam->id)
                    continue;

                if ($this->examsOverlap($exam, $otherExam)) {
                    // Room Check
                    if ($exam->salle_id === $otherExam->salle_id) {
                        $this->createConflict(
                            $exam->id,
                            $otherExam->id,
                            'room_overlap',
                            'critical',
                            "Room {$exam->lieuExamen->nom} double booked."
                        );
                    }
                    // Prof Check
                    if ($exam->prof_id === $otherExam->prof_id) {
                        $this->createConflict(
                            $exam->id,
                            $otherExam->id,
                            'professor_overlap',
                            'critical',
                            "Professor {$exam->professeur->nom} double booked."
                        );
                    }
                }
            }
        }

        // 4. Student Conflicts (Overlaps & Daily Limit)
        // Daily Limit > 1
        $studentDailyLoad = DB::table('inscriptions')
            ->join('examens', 'inscriptions.module_id', '=', 'examens.module_id')
            ->select('inscriptions.etudiant_id', DB::raw('CAST(examens.date_heure AS DATE) as exam_date'), DB::raw('count(*) as count'))
            ->groupBy('inscriptions.etudiant_id', 'exam_date')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($studentDailyLoad as $load) {
            // Find exams for this student on this day to link conflict
            $studentExams = DB::table('inscriptions')
                ->join('examens', 'inscriptions.module_id', '=', 'examens.module_id')
                ->where('inscriptions.etudiant_id', $load->etudiant_id)
                ->whereRaw('CAST(examens.date_heure AS DATE) = ?', [$load->exam_date])
                ->select('examens.id')
                ->get();

            foreach ($studentExams as $se) {
                $this->createConflict(
                    $se->id,
                    null,
                    'student_daily_limit',
                    'high',
                    "Student {$load->etudiant_id} has {$load->count} exams on {$load->exam_date}."
                );
            }
        }
    }

    private function createConflict($examId, $otherExamId, $type, $severity, $desc)
    {
        // Check if exists to avoid duplicates
        $exists = Conflict::where('exam_id', $examId)
            ->where('conflict_with_exam_id', $otherExamId)
            ->where('type', $type)
            ->exists();

        if (!$exists) {
            Conflict::create([
                'exam_id' => $examId,
                'conflict_with_exam_id' => $otherExamId,
                'type' => $type,
                'severity' => $severity,
                'description' => $desc,
                'resolved' => false
            ]);
        }
    }

    private function examsOverlap($examA, $examB)
    {
        $startA = Carbon::parse($examA->date_heure);
        $endA = $startA->copy()->addMinutes($examA->duree_minutes);

        $startB = Carbon::parse($examB->date_heure);
        $endB = $startB->copy()->addMinutes($examB->duree_minutes);

        return $startA->lt($endB) && $endA->gt($startB);
    }
}
