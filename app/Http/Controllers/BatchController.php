<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::query()->with('shift:id,name');

        $user = Auth::guard('sanctum')->user();
        if ($user && $user->role === 'hod') {
            // A logged-in HOD only sees batches under programs they created.
            $query->whereHas('program', function ($q) use ($user) {
                $q->where('hod_id', $user->id);
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        return response()->json($query->get()->each->append('name'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'shift_id' => 'required|exists:shifts,id',
            'start_year' => [
                'required',
                'integer',
                'digits:4',
                Rule::unique('batches')->where(function ($query) use ($request) {
                    return $query->where('program_id', $request->program_id)
                                  ->where('shift_id', $request->shift_id)
                                  ->where('end_year', $request->end_year);
                }),
            ],
            'end_year' => 'required|integer|digits:4|gte:start_year',
        ]);

        $program = Program::find($request->program_id);
        if ($program->hod_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized: not your program'], 403);
        }

        $batch = Batch::create($request->only('program_id', 'shift_id', 'start_year', 'end_year'));

        return response()->json(['message' => 'Batch created', 'batch' => $batch->append('name')], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $batch = Batch::with('program')->findOrFail($id);
        if (optional($batch->program)->hod_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $batch->delete();

        return response()->json(['message' => 'Batch deleted']);
    }
}
