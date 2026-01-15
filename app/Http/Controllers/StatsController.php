<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Etudiant;
use App\Models\Examen;
use App\Models\LieuExamen;
use App\Models\UnscheduledExam;
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
                'totalUnscheduled' => UnscheduledExam::count(),
                'occupancyRate' => self::calculateOccupancy(),
                'examsByDept' => self::getExamsByDept(),

                'unscheduledByReason' => self::getUnscheduledByReason(),
                'recentSchedules' => self::getRecentSchedules()
            ]
        ]);
    }

    private static function getRecentSchedules()
    {
        // Get validation status for each department
        // If no validation record exists, we can assume 'Draft' if exams exist?
        // For now, let's return actual validation records joined with departments.

        $validations = DB::table('exam_validations')
            ->join('departements', 'exam_validations.department_id', '=', 'departements.id')
            ->join('users', 'exam_validations.validated_by', '=', 'users.id')
            ->select(
                'departements.nom as dept',
                'exam_validations.status',
                'exam_validations.updated_at as date',
                'users.name as validator'
            )
            ->orderBy('exam_validations.updated_at', 'desc')
            ->get();

        // Also, if there are departments with exams but NO validation record, show them as 'Draft'
        // This is a bit more complex query. Let's simplify: return validations.
        // If the table is empty, frontend will show empty.
        // We can seed some data or just let it be.

        return $validations;
    }

    private static function calculateOccupancy()
    {
        $totalMinutesBooked = Examen::sum('duree_minutes');
        $totalRooms = LieuExamen::count();
        $totalCapacityMinutes = $totalRooms * 14 * 8 * 60; // 2 weeks, 8h/day
        return $totalCapacityMinutes > 0 ? round(($totalMinutesBooked / $totalCapacityMinutes) * 100, 2) : 0;
    }

    private static function getExamsByDept()
    {
        return DB::table('examens')
            ->join('modules', 'examens.module_id', '=', 'modules.id')
            ->join('formations', 'modules.formation_id', '=', 'formations.id')
            ->join('departements', 'formations.dept_id', '=', 'departements.id')
            ->select('departements.nom', DB::raw('count(*) as count'))
            ->groupBy('departements.nom')
            ->get();
    }

    private static function getUnscheduledByReason()
    {
        // Currently reason is generic, so let's maybe also group by dept for better visualization?
        // Or just group by reason.
        return UnscheduledExam::select('reason', DB::raw('count(*) as count'))
            ->groupBy('reason')
            ->get();
    }
}
