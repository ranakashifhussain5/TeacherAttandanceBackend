<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CrController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only CRs who signed up under a program this HOD owns.
        $query = User::where('role', 'cr')
            ->whereHas('program', function ($q) use ($user) {
                $q->where('hod_id', $user->id);
            })
            ->with('program:id,name', 'batch:id,start_year,end_year');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'hod') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'blocked'])],
            'reason' => 'nullable|string',
        ]);

        // Only allow a HOD to act on CRs who belong to one of their own programs.
        $cr = User::where('role', 'cr')
            ->whereHas('program', function ($q) use ($user) {
                $q->where('hod_id', $user->id);
            })
            ->findOrFail($id);

        $cr->status = $request->status;
        if ($request->status === 'blocked') {
            $cr->blocked_reason = $request->input('reason');
        }
        $cr->save();

        return response()->json(['message' => 'CR status updated', 'cr' => $cr]);
    }
}
