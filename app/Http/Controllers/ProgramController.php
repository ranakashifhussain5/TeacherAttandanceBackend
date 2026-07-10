<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramController extends Controller
{
    public function index()
    {
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

        $program = Program::create(['name' => $request->name]);

        return response()->json(['message' => 'Program created', 'program' => $program], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $program = Program::findOrFail($id);
        $program->delete();

        return response()->json(['message' => 'Program deleted']);
    }
}
