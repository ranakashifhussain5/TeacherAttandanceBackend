<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClassController extends Controller
{
    // List classes
    public function index()
    {
        $user = Auth::user();

        if($user->role === 'cr'){
            // CR sees only their classes in their department & session
            $classes = ClassRoom::where('cr_id', $user->id)
                ->where('department', $user->department)
                ->where('start_session', $user->start_session)
                ->where('end_session', $user->end_session)
                ->get();
        } else {
            // Admin sees all
            $classes = ClassRoom::all();
        }

        return response()->json($classes);
    }

    // Create new class
    public function store(Request $request)
    {
        $user = Auth::user();

        // If CR, auto assign department, session, and cr_id
        if($user->role === 'cr'){
            $request->merge([
                'department' => $user->department,
                'start_session' => $user->start_session,
                'end_session' => $user->end_session,
                'cr_id' => $user->id,
            ]);
        }

        $request->validate([
            'class_name'=>'required|string',
            'teacher_name'=>'required|string',
            'day'=>'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time'=>'required',
            'end_time'=>'required',
            'room'=>'required|string',
            'cr_id'=>'required|exists:users,id',
            'department'=>'required|string',
            'start_session'=>'required|integer',
            'end_session'=>'required|integer',
        ]);

        $class = ClassRoom::create($request->all());

        return response()->json(['message'=>'Class created','class'=>$class],201);
    }

    // Update class
    public function update(Request $request, $id)
    {
        $class = ClassRoom::findOrFail($id);
        $class->update($request->all());

        return response()->json(['message'=>'Class updated','class'=>$class]);
    }

    // Delete class
    public function destroy($id)
    {
        $class = ClassRoom::findOrFail($id);
        $class->delete();

        return response()->json(['message'=>'Class deleted']);
    }
    

public function todayClasses()
{
    $user = Auth::user();
    $today = Carbon::now()->format('l'); // Returns day name: Monday, Tuesday, etc.

    if($user->role === 'cr'){
        // CR sees only their classes for today
        $classes = ClassRoom::where('cr_id', $user->id)
            ->where('department', $user->department)
            ->where('start_session', $user->start_session)
            ->where('end_session', $user->end_session)
            ->where('day', $today)
            ->get();
    } else {
        // Admin sees all classes for today
        $classes = ClassRoom::where('day', $today)->get();
    }

    return response()->json($classes);
}

}
