<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramController extends Controller
{
    public function index()
    {
        $user = Auth::guard('sanctum')->user();

        // A logged-in HOD only sees the programs they created.
        // Anyone else (public CR-signup dropdown, or a CR) sees every program.
        if ($user && $user->role === 'hod') {
            return response()->json(Program::where('hod_id', $user->id)->get());
        }

        return response()->json(Program::all());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:programs,name',
        ]);

        $program = Program::create(['name' => $request->name, 'hod_id' => $user->id]);

        return response()->json(['message' => 'Program created', 'program' => $program], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $program = Program::findOrFail($id);
        if ($program->hod_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $program->delete();

        return response()->json(['message' => 'Program deleted']);
    }
}
