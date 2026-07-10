<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index()
    {
        return response()->json(Shift::all());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:shifts,name',
        ]);

        $shift = Shift::create(['name' => $request->name]);

        return response()->json(['message' => 'Shift created', 'shift' => $shift], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted']);
    }
}
