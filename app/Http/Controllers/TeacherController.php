<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Teachers belong to whichever HOD created them. A CR should only
        // see the teachers of the HOD who owns their own program.
        if ($user->role === 'hod') {
            $hodId = $user->id;
        } else {
            $hodId = optional(Program::find($user->program_id))->hod_id;
        }

        return response()->json(
            Teacher::where('hod_id', $hodId)->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('teachers')->where('hod_id', $user->id),
            ],
        ]);

        $teacher = Teacher::create(['name' => $request->name, 'hod_id' => $user->id]);

        return response()->json(['message' => 'Teacher created', 'teacher' => $teacher], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher = Teacher::findOrFail($id);
        if ($teacher->hod_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted']);
    }
}
