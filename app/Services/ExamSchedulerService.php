<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Examen;

class ExamSchedulerService
{
    private $modules;
    private $rooms;
    private $professors;
    private $conflictGraph = []; // module_id => [conflicting_module_ids]
    private $assignments = [];
    private $slots = [];

    public function generate()
    {
        $startTime = microtime(true);
        DB::disableQueryLog();

        // 1. Fetch Data (Optimized)
        $this->loadData();

        // 2. Build In-Memory Conflict Graph
        $this->buildConflictGraph();

        // 3. Sort Modules (Heuristic: Degree DESC, Size DESC)
        $sortedModuleIds = $this->sortModules();

        // 4. Greedy Assignment Loop
        $schedule = [];
        $scheduledCount = 0;

        // Define Slots (Example: 2 weeks, 8:00-18:00, 2h slots)
        // This should simpler be configurable. For now, hardcoded standard university slots.
        $this->generateSlots();

        foreach ($sortedModuleIds as $moduleId) {
            $module = $this->modules[$moduleId];
            $bestSlot = null;
            $bestRoom = null;
            $bestProf = null;

            // Try to find a valid slot
            foreach ($this->slots as $slot) {
                if (!$this->isSlotValid($moduleId, $slot, $schedule)) {
                    continue;
                }

                // Find Best Fit Room
                $room = $this->findBestRoom($module, $slot, $schedule);
                if (!$room)
                    continue;

                // Find Professor
                $prof = $this->assignProfessor($module, $slot, $schedule);
                if (!$prof)
                    continue; // Cannot schedule without prof

                // Success
                $bestSlot = $slot;
                $bestRoom = $room;
                $bestProf = $prof;
                break; // Take first valid (Greedy)
            }

            if ($bestSlot) {
                $schedule[$moduleId] = [
                    'module_id' => $moduleId,
                    'prof_id' => $bestProf->id,
                    'salle_id' => $bestRoom->id,
                    'date_heure' => $bestSlot['start'],
                    'duree_minutes' => 120 // Standard 2h
                ];
                $scheduledCount++;
            } else {
                // Failed to schedule module
                // Log warning or throw error depending on strictness
            }
        }

        // 5. Batch Insert
        $this->persistSchedule($schedule);

        return [
            'success' => true,
            'scheduled' => $scheduledCount,
            'total' => count($this->modules),
            'time' => round(microtime(true) - $startTime, 2) . 's'
        ];
    }

    private function loadData()
    {
        // Load Modules with student count AND formation info (dept_id)
        $this->modules = DB::table('modules')
            ->join('inscriptions', 'modules.id', '=', 'inscriptions.module_id')
            ->join('formations', 'modules.formation_id', '=', 'formations.id')
            ->select(
                'modules.id',
                'modules.formation_id',
                'formations.dept_id',
                DB::raw('count(inscriptions.id) as student_count')
            )
            ->groupBy('modules.id', 'modules.formation_id', 'formations.dept_id')
            ->get()
            ->keyBy('id')
            ->all();

        // Load Rooms sorted by capacity ASC (Best Fit)
        $this->rooms = DB::table('lieu_examen')
            ->orderBy('capacite', 'asc')
            ->get()
            ->all();

        // Load Professors mixed selection
        $this->professors = DB::table('professeurs')->get()->all();
    }

    private function buildConflictGraph()
    {
        // 130k rows - Memory efficient approach
        // Group module_ids by student_id
        $inscriptions = DB::table('inscriptions')
            ->select('etudiant_id', 'module_id')
            ->orderBy('etudiant_id')
            ->cursor(); // Use cursor to stream

        $studentModules = [];
        $currentStudent = null;
        $modulesForStudent = [];

        foreach ($inscriptions as $row) {
            if ($row->etudiant_id !== $currentStudent) {
                // Process previous student
                if ($currentStudent !== null) {
                    $this->addCliqueToGraph($modulesForStudent);
                }
                $currentStudent = $row->etudiant_id;
                $modulesForStudent = [];
            }
            $modulesForStudent[] = $row->module_id;
        }
        // Process last student
        if (!empty($modulesForStudent)) {
            $this->addCliqueToGraph($modulesForStudent);
        }
    }

    private function addCliqueToGraph($moduleIds)
    {
        $count = count($moduleIds);
        if ($count < 2)
            return;

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $moduleIds[$i];
                $b = $moduleIds[$j];

                $this->conflictGraph[$a][$b] = true;
                $this->conflictGraph[$b][$a] = true;
            }
        }
    }

    private function sortModules()
    {
        $ids = array_keys($this->modules);

        usort($ids, function ($a, $b) {
            // 1. Degree (Conflict count) DESC
            $degreeA = isset($this->conflictGraph[$a]) ? count($this->conflictGraph[$a]) : 0;
            $degreeB = isset($this->conflictGraph[$b]) ? count($this->conflictGraph[$b]) : 0;

            if ($degreeA !== $degreeB) {
                return $degreeB - $degreeA;
            }

            // 2. Size (Student count) DESC
            return $this->modules[$b]->student_count - $this->modules[$a]->student_count;
        });

        return $ids;
    }

    private function generateSlots()
    {
        // Generate 2 weeks of slots (Excluding weekends ideally, but keeping simple)
        // 5 slots per day: 08:30, 10:30, 12:30, 14:30, 16:30
        $startDate = Carbon::now()->next('Monday');
        $days = 12; // 2 weeks (6 days/week)

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $times = ['08:30', '10:30', '12:30', '14:30', '16:30'];

            foreach ($times as $time) {
                $start = $date->format('Y-m-d') . ' ' . $time . ':00';
                $this->slots[] = [
                    'start' => $start,
                    'timestamp' => strtotime($start),
                    'day' => $date->format('Y-m-d')
                ];
            }
        }
    }

    private function isSlotValid($moduleId, $slot, $schedule)
    {
        // Check 1: Conflict Graph (Student Overlap)
        // If any neighbor in conflict graph is scheduled at this exact time -> INVALID
        if (isset($this->conflictGraph[$moduleId])) {
            foreach ($this->conflictGraph[$moduleId] as $conflictingModuleId => $u) {
                if (isset($schedule[$conflictingModuleId])) {
                    if ($schedule[$conflictingModuleId]['date_heure'] === $slot['start']) {
                        return false; // Soft/Hard constraint: No overlap
                    }

                    // Student 1 exam/day check (Hard Constraint)
                    // If conflicting module (shared student) is same day -> INVALID
                    $scheduledDay = date('Y-m-d', strtotime($schedule[$conflictingModuleId]['date_heure']));
                    if ($scheduledDay === $slot['day']) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function findBestRoom($module, $slot, $schedule)
    {
        // Filter rooms that fit student count
        // Already sorted by capacity ASC
        foreach ($this->rooms as $room) {
            if ($room->capacite >= $module->student_count) {
                // Check if room is free at this slot
                if (!$this->isRoomBooked($room->id, $slot['start'], $schedule)) {
                    return $room;
                }
            }
        }
        return null;
    }

    private function isRoomBooked($roomId, $timeStart, $schedule)
    {
        foreach ($schedule as $s) {
            if ($s['salle_id'] == $roomId && $s['date_heure'] == $timeStart) {
                return true;
            }
        }
        return false;
    }

    private function assignProfessor($module, $slot, $schedule)
    {
        // Filter available professors
        // Constraints: Not booked at time, Max 3/day

        $candidates = [];
        $candidatesData = []; // Store scores for sorting

        foreach ($this->professors as $prof) {
            if ($this->isProfBooked($prof->id, $slot, $schedule))
                continue;

            // Score
            $score = 0;

            // 1. Soft Constraint: Same Department (+10)
            if (isset($module->dept_id) && isset($prof->dept_id) && $prof->dept_id == $module->dept_id) {
                $score += 10;
            }

            // 2. Soft Constraint: Fairness/Load Balance (-5 if high load)
            // Calculate current load
            $currentLoad = $this->getProfTotalLoad($prof->id, $schedule);
            if ($currentLoad > 5) {
                $score -= 5;
            }

            $candidates[] = $prof;
            $candidatesData[$prof->id] = $score;
        }

        if (empty($candidates))
            return null;

        // Sort by Score DESC
        usort($candidates, function ($a, $b) use ($candidatesData) {
            return $candidatesData[$b->id] - $candidatesData[$a->id];
        });

        // Return highest scorer
        return $candidates[0];
    }

    private function getProfTotalLoad($profId, $schedule)
    {
        $count = 0;
        foreach ($schedule as $s) {
            if ($s['prof_id'] == $profId)
                $count++;
        }
        return $count;
    }

    private function isProfBooked($profId, $slot, $schedule)
    {
        $dailyCount = 0;
        foreach ($schedule as $s) {
            if ($s['prof_id'] == $profId) {
                // Overlap check
                if ($s['date_heure'] == $slot['start'])
                    return true;

                // Daily limit check
                if (date('Y-m-d', strtotime($s['date_heure'])) === $slot['day']) {
                    $dailyCount++;
                }
            }
        }

        return $dailyCount >= 3;
    }

    private function persistSchedule($schedule)
    {
        if (empty($schedule))
            return;

        DB::transaction(function () use ($schedule) {
            // Optional: Clear existing exams?
            // Examen::truncate(); 

            $chunks = array_chunk($schedule, 500); // Batch insert
            $now = now();
            foreach ($chunks as $chunk) {
                // Add timestamps
                foreach ($chunk as &$record) {
                    $record['created_at'] = $now;
                    $record['updated_at'] = $now;
                }
                Examen::insert($chunk); // Using Model or DB::table depending on timestamps requirement
            }
        }, 5); // 5 attempts
    }
}
