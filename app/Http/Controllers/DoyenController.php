<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Examen;
use App\Models\Conflict;
use App\Models\User;
use App\Models\Departement;
use App\Models\LieuExamen;
use App\Models\ExamValidation; // We'll create this model too
use Illuminate\Support\Facades\DB;
use App\Services\ConflictDetectionService;

class DoyenController extends Controller
{
    protected $conflictService;

    public function __construct(ConflictDetectionService $conflictService)
    {
        $this->conflictService = $conflictService;
        // middleware check can happen here or in routes
    }

    public function dashboard()
    {
        // 1. Global KPIs
        $totalExams = Examen::count();
        $totalUnscheduled = \App\Models\UnscheduledExam::count();

        // Occupancy Rate (Simplistic: exams * duration / total capacity * slots? 
        // Better: sum(duration) / (rooms * working_hours_in_exam_period))
        // For now, let's just count total hours booked.
        $totalMinutesBooked = Examen::sum('duree_minutes');
        $totalRooms = LieuExamen::count();
        // Assume 2 week exam period * 8 hours/day * 60 mins
        $totalCapacityMinutes = $totalRooms * 14 * 8 * 60;
        $occupancyRate = $totalCapacityMinutes > 0 ? ($totalMinutesBooked / $totalCapacityMinutes) * 100 : 0;

        $unscheduledByDept = DB::table('unscheduled_exams')
            ->join('formations', 'unscheduled_exams.formation_id', '=', 'formations.id')
            ->join('departements', 'formations.dept_id', '=', 'departements.id')
            ->select('departements.nom', DB::raw('count(*) as count'))
            ->groupBy('departements.nom')
            ->get();

        // Validation Status
        $globalValidation = DB::table('exam_validations')
            ->whereNull('department_id')
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'kpis' => [
                'total_exams' => $totalExams,
                'occupancy_rate' => round($occupancyRate, 2),
                'total_unscheduled' => $totalUnscheduled,
            ],
            'unscheduled_by_dept' => $unscheduledByDept,
            'validation_status' => $globalValidation ? $globalValidation->status : 'draft'
        ]);
    }

    public function schedule(Request $request)
    {
        $query = Examen::with(['module.formation.departement', 'professeur', 'lieuExamen']);

        if ($request->has('dept_id')) {
            $query->whereHas('module.formation', function ($q) use ($request) {
                $q->where('dept_id', $request->dept_id);
            });
        }

        return $query->get();
    }

    public function detectConflicts()
    {
        $this->conflictService->detectAll();
        return response()->json(['message' => 'Conflict detection ran successfully.']);
    }

    public function validateSchedule(Request $request)
    {
        // Global validation
        // Insert into exam_validations
        DB::table('exam_validations')->updateOrInsert(
            ['department_id' => null], // Global
            [
                'status' => 'validated_doyen',
                'validated_by' => $request->user()->id,
                'comments' => $request->comments,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return response()->json(['message' => 'Global schedule validated.']);
    }

    public function invalidateSchedule(Request $request)
    {
        // Check current status
        $val = DB::table('exam_validations')->whereNull('department_id')->first();

        if ($val && $val->status === 'validated_doyen') {
            DB::table('exam_validations')
                ->whereNull('department_id')
                ->update(['status' => 'draft', 'updated_at' => now()]);
            return response()->json(['message' => 'Validation cancelled.']);
        }

        return response()->json(['error' => 'Cannot cancel validation (already published or not found).'], 400);
    }
}
