<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // ------------------------
    // LIST ATTENDANCE
    // ------------------------
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Attendance::with('classRoom:id,class_name,teacher_id,start_time,end_time,room');

        if ($user->role === 'cr') {
            $query->where('cr_id', $user->id);
        } elseif ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return response()->json($attendances);
    }

    // ------------------------
    // ARRIVED MARK
    // ------------------------
    public function markArrived(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // Check Weekend
        $today = Carbon::now();
        if ($today->dayOfWeek == 0 || $today->dayOfWeek == 6) {
            return response()->json([
                'message' => 'Attendance cannot be recorded on Saturday or Sunday.',
            ], 403);
        }

        $class = ClassRoom::find($request->class_id);

        // Prevent double arrived entry
        $attendance = Attendance::where('class_id', $class->id)
            ->where('date', $today->toDateString())
            ->first();

        if ($attendance && $attendance->arrived_time) {
            return response()->json([
                'message' => 'Arrived already marked for today.',
            ], 409);
        }

        // Late Check (30 mins rule)
        $classStart = Carbon::createFromTimeString($class->start_time);
        $isLate = $today->greaterThan($classStart->copy()->addMinutes(30));

        $status = $isLate ? 'late' : 'present';

        if (!$attendance) {
            $attendance = Attendance::create([
                'class_id' => $class->id,
                'cr_id'    => auth()->id(),
                'date'     => $today->toDateString(),
                'arrived_time' => $today->toTimeString(),
                'status'   => $status,
            ]);
        } else {
            $attendance->update([
                'arrived_time' => $today->toTimeString(),
                'status' => $status,
            ]);
        }

        return response()->json([
            'message' => 'Arrived marked successfully.',
            'data' => $attendance,
        ]);
    }

    // ------------------------
    // LEFT MARK
    // ------------------------
    public function markLeft(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // Block Weekend
        $today = Carbon::now();
        if ($today->dayOfWeek == 0 || $today->dayOfWeek == 6) {
            return response()->json([
                'message' => 'Attendance cannot be recorded on Saturday or Sunday.',
            ], 403);
        }

        $attendance = Attendance::where('class_id', $request->class_id)
            ->where('date', $today->toDateString())
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'You must mark Arrived before Left.',
            ], 404);
        }

        if ($attendance->left_time) {
            return response()->json([
                'message' => 'Left already marked for today.',
            ], 409);
        }

        $attendance->update([
            'left_time' => $today->toTimeString(),
        ]);

        return response()->json([
            'message' => 'Left marked successfully.',
            'data' => $attendance,
        ]);
    }

    // ------------------------
    // ABSENT MARK
    // ------------------------
    public function markAbsent(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $today = Carbon::now();
        if ($today->dayOfWeek == 0 || $today->dayOfWeek == 6) {
            return response()->json([
                'message' => 'Attendance cannot be recorded on Saturday or Sunday.',
            ], 403);
        }

        $attendance = Attendance::where('class_id', $request->class_id)
            ->where('date', $today->toDateString())
            ->first();

        if ($attendance && $attendance->arrived_time) {
            return response()->json([
                'message' => 'Arrived already marked for today, cannot mark absent.',
            ], 409);
        }

        if ($attendance && $attendance->status === 'absent') {
            return response()->json([
                'message' => 'Absent already marked for today.',
            ], 409);
        }

        if (!$attendance) {
            $attendance = Attendance::create([
                'class_id' => $request->class_id,
                'cr_id'    => auth()->id(),
                'date'     => $today->toDateString(),
                'status'   => 'absent',
            ]);
        } else {
            $attendance->update(['status' => 'absent']);
        }

        return response()->json([
            'message' => 'Absent marked successfully.',
            'data' => $attendance,
        ]);
    }
}
