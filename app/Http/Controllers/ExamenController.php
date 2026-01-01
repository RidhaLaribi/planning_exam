<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\Etudiant;
use App\Http\Requests\StoreExamenRequest;
use App\Http\Requests\UpdateExamenRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    public function index()
    {
        return Examen::with(['module', 'professeur', 'lieuExamen'])->get();
    }

    public function store(StoreExamenRequest $request)
    {
        $examen = Examen::create($request->validated());
        return response()->json($examen, 201);
    }

    public function show(Examen $examen)
    {
        return $examen->load(['module', 'professeur', 'lieuExamen']);
    }

    public function update(UpdateExamenRequest $request, Examen $examen)
    {
        $examen->update($request->validated());
        return $examen;
    }

    public function destroy(Examen $examen)
    {
        $examen->delete();
        return response()->noContent();
    }

    // Advanced Queries

    public function schedule()
    {
        // Join all related tables for a full schedule view
        return Examen::with([
            'module.formation.departement',
            'professeur',
            'lieuExamen'
        ])->get();
    }

    public function byDepartement($deptId)
    {
        return Examen::whereHas('module.formation', function ($q) use ($deptId) {
            $q->where('dept_id', $deptId);
        })->with(['module', 'professeur', 'lieuExamen'])->get();
    }

    public function byEtudiant($etudiantId)
    {
        // Exams for modules the student is enrolled in
        return Examen::whereHas('module.etudiants', function ($q) use ($etudiantId) {
            $q->where('etadiants.id', $etudiantId); // Note: table name usually plural 'etudiants' or via pivot
        })
        ->orWhereHas('module.etudiants', function($q) use ($etudiantId){
             $q->where('etudiants.id', $etudiantId);
        })
        ->with(['module', 'professeur', 'lieuExamen'])->get();
    }

    public function byProfesseur($profId)
    {
        return Examen::where('prof_id', $profId)
            ->with(['module', 'lieuExamen'])
            ->get();
    }

    public function detectConflicts()
    {
        $conflicts = [];
        $exams = Examen::with(['module', 'professeur', 'lieuExamen'])->get();

        // 1. Room Overlaps & 2. Professor Overlaps
        foreach ($exams as $examA) {
            foreach ($exams as $examB) {
                if ($examA->id >= $examB->id) continue; // Avoid duplicate checks

                if ($this->examsOverlap($examA, $examB)) {
                    if ($examA->salle_id === $examB->salle_id) {
                        $conflicts[] = [
                            'type' => 'Room Conflict',
                            'message' => "Room {$examA->lieuExamen->nom} is double booked.",
                            'exam_1' => $examA->id,
                            'exam_2' => $examB->id,
                        ];
                    }
                    if ($examA->prof_id === $examB->prof_id) {
                        $conflicts[] = [
                            'type' => 'Professor Conflict',
                            'message' => "Professor {$examA->professeur->nom} has overlapping exams.",
                            'exam_1' => $examA->id,
                            'exam_2' => $examB->id,
                        ];
                    }
                }
            }
        }

        // 3. Student Same-Day Exams (or overlapping)
        // Group exams by day and checking students
        // This is expensive to check exhaustively in PHP for all students, so we'll check for *congested* days per student?
        // Or just listing students who have > 1 exam per day.
        
        // Optimized approach: SQL group by student and date
        $studentConflicts = DB::table('inscriptions')
            ->join('examens', 'inscriptions.module_id', '=', 'examens.module_id')
            ->select('inscriptions.etudiant_id', DB::raw('DATE(examens.date_heure) as exam_date'), DB::raw('count(*) as exam_count'))
            ->groupBy('inscriptions.etudiant_id', 'exam_date')
            ->having('exam_count', '>', 1)
            ->get();

        foreach ($studentConflicts as $sc) {
            $conflicts[] = [
                'type' => 'Student Conflict',
                'message' => "Student ID {$sc->etudiant_id} has {$sc->exam_count} exams on {$sc->exam_date}.",
                'student_id' => $sc->etudiant_id,
                'date' => $sc->exam_date,
            ];
        }

        return response()->json($conflicts);
    }

    private function examsOverlap($examA, $examB)
    {
        $startA = \Carbon\Carbon::parse($examA->date_heure);
        $endA = $startA->copy()->addMinutes($examA->duree_minutes);
        
        $startB = \Carbon\Carbon::parse($examB->date_heure);
        $endB = $startB->copy()->addMinutes($examB->duree_minutes);

        return $startA->lt($endB) && $endA->gt($startB);
    }
}
