<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportsController extends Controller
{
    /**
     * Flexible reports endpoint.
     *
     * Query params:
     * - type = today | weekly | monthly | classwise | status_summary
     * - start_date, end_date (YYYY-MM-DD)
     * - month (1-12) & year (YYYY)  -> for monthly
     * - class_id
     * - teacher_name
     *
     * Example:
     * /api/reports?type=monthly&month=11&year=2025
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'type' => ['nullable', Rule::in(['today','weekly','monthly','classwise','status_summary'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:1900',
            'class_id' => 'nullable|exists:classes,id',
            'teacher_name' => 'nullable|string',
        ]);

        $type = $request->input('type', 'today'); // default to today if not provided

        // Build base query (join classes so we can group by teacher etc.)
        $query = Attendance::query()
            ->select('attendances.*')
            ->with(['classRoom' => function($q){
                $q->select('id','class_name','teacher_name','start_time','end_time','day','room','cr_id','department','start_session','end_session');
            }]);

        // If CR: restrict to their cr_id and their department/session (as we did in classes)
        if ($user->role === 'cr') {
            $query->where('attendances.cr_id', $user->id);
            // optionally ensure class matches user's session/department:
            $query->whereHas('classRoom', function($q) use ($user){
                $q->where('department', $user->department)
                  ->where('start_session', $user->start_session)
                  ->where('end_session', $user->end_session);
            });
        } else {
            // Admin: can filter by teacher_name or class_id if provided
            if ($request->filled('teacher_name')) {
                $tn = $request->teacher_name;
                $query->whereHas('classRoom', function($q) use($tn){
                    $q->where('teacher_name', 'like', "%$tn%");
                });
            }
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
        }

        // Date range determination (based on type)
        switch ($type) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today();
                break;

            case 'weekly':
                // Use week starting Monday
                $start = Carbon::now()->startOfWeek(); // Monday
                $end = Carbon::now()->endOfWeek();     // Sunday
                break;

            case 'monthly':
                $month = $request->input('month', Carbon::now()->month);
                $year  = $request->input('year', Carbon::now()->year);
                $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                break;

            case 'classwise':
                // If user provided start_date/end_date, use them; otherwise full history or session-limited for CR.
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = Carbon::parse($request->start_date)->startOfDay();
                    $end = Carbon::parse($request->end_date)->endOfDay();
                } else {
                    // default to all records (no date bounding)
                    $start = null;
                    $end = null;
                }
                break;

            case 'status_summary':
                // allow optional start/end_date or month/year, default to current month
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = Carbon::parse($request->start_date)->startOfDay();
                    $end = Carbon::parse($request->end_date)->endOfDay();
                } else {
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                }
                break;

            default:
                $start = Carbon::today();
                $end = Carbon::today();
        }

        // Apply date filter if set
        if ($start && $end) {
            $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        }

        // Exclude Sundays (MySQL DAYOFWEEK(date) = 1 => Sunday)
        // Prefer DB-level filter for efficiency; fallback to collection filter if DB not MySQL.
        try {
            // this will work on MySQL
            $query->whereRaw('DAYOFWEEK(attendances.date) != 1');
        } catch (\Exception $e) {
            // If DB does not support DAYOFWEEK, we'll filter after fetching
            $filterSundayInCollection = true;
        }

        // Execute different aggregations based on type
        if ($type === 'today' || $type === 'weekly' || $type === 'monthly' || $type === 'classwise') {
            // Return list of attendance records, joined with class info
            $rows = $query->orderBy('date', 'desc')->get();

            // If DB-level Sunday filter didn't run, drop Sundays here
            if (!empty($filterSundayInCollection)) {
                $rows = $rows->reject(function($item){
                    return Carbon::parse($item->date)->isSunday();
                })->values();
            }

            // Map into frontend-friendly shape:
            $data = $rows->map(function($att){
                return [
                    'attendance_id' => $att->id,
                    'date' => $att->date,
                    'class_id' => $att->class_id,
                    'class_name' => optional($att->classRoom)->class_name,
                    'teacher_name' => optional($att->classRoom)->teacher_name,
                    'start_time' => optional($att->classRoom)->start_time,
                    'end_time' => optional($att->classRoom)->end_time,
                    'room' => optional($att->classRoom)->room,
                    'arrived_time' => $att->arrived_time,
                    'left_time' => $att->left_time,
                    'status' => $att->status,
                    'cr_id' => $att->cr_id,
                ];
            });

            return response()->json([
                'type' => $type,
                'start' => $start ? $start->toDateString() : null,
                'end' => $end ? $end->toDateString() : null,
                'count' => $data->count(),
                'data' => $data,
            ]);
        }

        if ($type === 'status_summary') {
            // Return counts grouped by status overall, and by teacher_name if requested
            // We'll build SQL aggregation for efficiency

            // base query for counts
            $aggQuery = Attendance::query()
                ->select('attendances.status', DB::raw('COUNT(*) as total'))
                ->join('classes','attendances.class_id','=','classes.id');

            // apply same CR restriction for CR users
            if ($user->role === 'cr') {
                $aggQuery->where('attendances.cr_id', $user->id)
                         ->where('classes.department', $user->department)
                         ->where('classes.start_session', $user->start_session)
                         ->where('classes.end_session', $user->end_session);
            } else {
                if ($request->filled('teacher_name')) {
                    $aggQuery->where('classes.teacher_name','like','%'.$request->teacher_name.'%');
                }
                if ($request->filled('class_id')) {
                    $aggQuery->where('attendances.class_id', $request->class_id);
                }
            }

            if ($start && $end) {
                $aggQuery->whereBetween('attendances.date', [$start->toDateString(), $end->toDateString()]);
            }

            // Exclude Sundays at DB level if possible
            try {
                $aggQuery->whereRaw('DAYOFWEEK(attendances.date) != 1');
            } catch (\Exception $e) {
                // ignore
            }

            $summary = $aggQuery->groupBy('attendances.status')->get()->pluck('total','status');

            // Optionally: teacher-wise breakdown
            $teacherBreakdown = [];
            if ($request->filled('teacher_name') || $request->filled('class_id') || $request->input('group_by_teacher', false)) {
                // group by teacher_name and status
                $tb = Attendance::select('classes.teacher_name', 'attendances.status', DB::raw('COUNT(*) as total'))
                    ->join('classes','attendances.class_id','=','classes.id');

                if ($user->role === 'cr') {
                    $tb->where('attendances.cr_id', $user->id)
                       ->where('classes.department', $user->department)
                       ->where('classes.start_session', $user->start_session)
                       ->where('classes.end_session', $user->end_session);
                } else {
                    if ($request->filled('teacher_name')) {
                        $tb->where('classes.teacher_name','like','%'.$request->teacher_name.'%');
                    }
                    if ($request->filled('class_id')) {
                        $tb->where('attendances.class_id', $request->class_id);
                    }
                }

                if ($start && $end) {
                    $tb->whereBetween('attendances.date', [$start->toDateString(), $end->toDateString()]);
                }

                try {
                    $tb->whereRaw('DAYOFWEEK(attendances.date) != 1');
                } catch (\Exception $e) {}

                $tb = $tb->groupBy('classes.teacher_name', 'attendances.status')
                         ->get();

                // reshape into: [ teacher => { present: x, late: y, absent: z } ]
                foreach ($tb as $row) {
                    $teacher = $row->teacher_name ?? 'Unknown';
                    $status = $row->status;
                    $count = $row->total;

                    if (!isset($teacherBreakdown[$teacher])) $teacherBreakdown[$teacher] = ['present'=>0,'late'=>0,'absent'=>0];
                    $teacherBreakdown[$teacher][$status] = $count;
                }
            }

            return response()->json([
                'type' => 'status_summary',
                'start' => $start ? $start->toDateString() : null,
                'end' => $end ? $end->toDateString() : null,
                'summary' => [
                    'present' => (int) ($summary['present'] ?? 0),
                    'late' => (int) ($summary['late'] ?? 0),
                    'absent' => (int) ($summary['absent'] ?? 0),
                ],
                'teacher_breakdown' => $teacherBreakdown,
            ]);
        }

        // fallback empty
        return response()->json(['message' => 'No results'], 200);
    }
}
