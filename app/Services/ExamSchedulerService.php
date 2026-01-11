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

    // Optimization: O(1) Lookup Tables
    private $professorBookings = []; // [prof_id][timestamp] = true
    private $roomBookings = [];      // [room_id][timestamp] = true
    private $profDailyCounts = [];   // [prof_id][date] = count

    public function generate()
    {
        $startTime = microtime(true);
        DB::disableQueryLog();

        // 1. Fetch Data
        $this->loadData();

        // 2. Build In-Memory Conflict Graph
        $this->buildConflictGraph();

        // 3. Sort Modules
        $sortedModuleIds = $this->sortModules();

        // 4. Greedy Assignment Loop
        $schedule = [];
        $scheduledCount = 0;

        // Define Slots
        $this->generateSlots();

        foreach ($sortedModuleIds as $moduleId) {
            $module = $this->modules[$moduleId];
            $bestSlot = null;
            $allocations = null;

            // Try to find a valid slot
            foreach ($this->slots as $slot) {
                if (!$this->isSlotValid($moduleId, $slot, $schedule)) {
                    continue;
                }

                // 1. Multi-Room Allocation
                $selectedRooms = $this->findRoomAllocation($module->student_count, $slot, $schedule);
                if (empty($selectedRooms)) {
                    continue;
                }

                // 2. Professor Assignment (One per room)
                $assignedProfs = $this->assignProfessorsForRooms($selectedRooms, $module, $slot, $schedule);
                if (empty($assignedProfs)) {
                    // Cannot find enough professors for these rooms
                    continue;
                }

                // Success
                $bestSlot = $slot;

                // Pair Rooms with Profs
                $allocations = [];
                $studentsAssigned = 0;
                $totalStudents = $module->student_count;

                // Track Bookings (Optimization)
                foreach ($selectedRooms as $index => $room) {
                    $prof = $assignedProfs[$index];
                    $ts = $bestSlot['timestamp'];
                    $day = $bestSlot['day'];

                    $this->roomBookings[$room->id][$ts] = true;
                    $this->professorBookings[$prof->id][$ts] = true;

                    if (!isset($this->profDailyCounts[$prof->id][$day])) {
                        $this->profDailyCounts[$prof->id][$day] = 0;
                    }
                    $this->profDailyCounts[$prof->id][$day]++;

                    $allocations[] = [
                        'salle_id' => $room->id,
                        'prof_id' => $prof->id,
                        'capacity' => $room->capacite
                    ];
                }
                break; // Take first valid
            }

            if ($bestSlot) {
                // Save to schedule
                $schedule[$moduleId] = [
                    'module_id' => $moduleId,
                    'date_heure' => $bestSlot['start'],
                    'allocations' => $allocations,
                    'duree_minutes' => 120 // Standard 2h
                ];
                $scheduledCount++;
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

    private function findRoomAllocation($studentCount, $slot, $schedule)
    {
        $selectedRooms = [];
        $currentCapacity = 0;

        // Rooms are already sorted by capacity ASC in loadData
        // For multi-room, we want to minimize Room Count, so we should try Largest rooms first?
        // If we use Smallest First, we might use many small rooms.
        // If we use Largest First, we use fewer rooms.
        // Requirement: "Select the largest available rooms first"

        // So we need to reverse the rooms list or sort it DESC for this operation
        // But $this->rooms is shared. Let's make a local copy sorted DESC.
        $roomsDesc = array_reverse($this->rooms);

        foreach ($roomsDesc as $room) {
            if ($currentCapacity >= $studentCount) {
                break;
            }

            // Check if room is free
            if (!$this->isRoomBooked($room->id, $slot['start'], $schedule)) {
                $selectedRooms[] = $room;
                $currentCapacity += $room->capacite;
            }
        }

        if ($currentCapacity < $studentCount) {
            return []; // Not enough capacity found
        }

        return $selectedRooms;
    }

    private function isRoomBooked($roomId, $timeStart, $schedule)
    {
        // O(1) Optimization
        $ts = strtotime($timeStart);
        return isset($this->roomBookings[$roomId][$ts]);
    }

    private function assignProfessorsForRooms($rooms, $module, $slot, $schedule)
    {
        $assignedProfs = [];
        $usedProfIds = [];

        foreach ($rooms as $room) {
            $prof = $this->findBestProfessor($module, $slot, $schedule, $usedProfIds);

            if (!$prof) {
                return []; // Fail if we can't staff a room
            }

            $assignedProfs[] = $prof;
            $usedProfIds[] = $prof->id;
        }

        return $assignedProfs;
    }

    private function findBestProfessor($module, $slot, $schedule, $excludeProfIds = [])
    {
        $candidates = [];
        $candidatesData = [];

        foreach ($this->professors as $prof) {
            if (in_array($prof->id, $excludeProfIds))
                continue;

            if ($this->isProfBooked($prof->id, $slot, $schedule))
                continue;

            // Score Logic
            $score = 0;
            if (isset($module->dept_id) && isset($prof->dept_id) && $prof->dept_id == $module->dept_id) {
                $score += 10;
            }
            $currentLoad = $this->getProfTotalLoad($prof->id, $schedule);
            if ($currentLoad > 5) {
                $score -= 5;
            }

            $candidates[] = $prof;
            $candidatesData[$prof->id] = $score;
        }

        if (empty($candidates))
            return null;

        usort($candidates, function ($a, $b) use ($candidatesData) {
            return $candidatesData[$b->id] - $candidatesData[$a->id];
        });

        return $candidates[0];
    }

    private function getProfTotalLoad($profId, $schedule)
    {
        // Could be optimized further with a counter array, but let's stick to simple first.
        // Actually, we can just aggregate daily counts
        // BUT for now, let's leave this one or optimize it slightly. 
        // We can add a totalLoad counter.
        // Let's implement dynamic total load tracking.
        return array_sum($this->profDailyCounts[$profId] ?? []);
    }

    private function isProfBooked($profId, $slot, $schedule)
    {
        // O(1) Optimization
        $ts = $slot['timestamp'];
        if (isset($this->professorBookings[$profId][$ts])) {
            return true;
        }

        // Daily Limit Check
        $day = $slot['day'];
        $dailyCount = $this->profDailyCounts[$profId][$day] ?? 0;

        return $dailyCount >= 3;
    }

    private function persistSchedule($schedule)
    {
        if (empty($schedule))
            return;

        DB::transaction(function () use ($schedule) {
            // Optional: Clear existing exams?
            Examen::truncate();

            $insertData = [];
            $now = now();

            foreach ($schedule as $moduleId => $data) {
                $module = $this->modules[$moduleId];

                // Deterministic Student Distribution
                // Logic for distribution is implied by the exam creation.
                // We are not storing student-room mapping in specific tables in this iteration,
                // so we don't need to fetch the student list here.

                // If specific student assignment is needed later, we should batch this or do it separately.

                // Optimization: Removed N+1 query that fetched students but didn't use them.

                // If I am strictly creating `examens` rows:
                foreach ($data['allocations'] as $alloc) {
                    $insertData[] = [
                        'module_id' => $moduleId,
                        'salle_id' => $alloc['salle_id'],
                        'prof_id' => $alloc['prof_id'],
                        'date_heure' => $data['date_heure'],
                        'duree_minutes' => $data['duree_minutes'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            $chunks = array_chunk($insertData, 500);
            foreach ($chunks as $chunk) {
                Examen::insert($chunk);
            }
        }, 5);
    }
}
