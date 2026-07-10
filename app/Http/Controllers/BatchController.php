<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BatchController extends Controller
{
    public function index()
    {
        return response()->json(Batch::all()->each->append('name'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'start_year' => 'required|integer|digits:4',
            'end_year' => 'required|integer|digits:4|gte:start_year',
        ]);

        $batch = Batch::create($request->only('start_year', 'end_year'));

        return response()->json(['message' => 'Batch created', 'batch' => $batch->append('name')], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $batch = Batch::findOrFail($id);
        $batch->delete();

        return response()->json(['message' => 'Batch deleted']);
    }
}
