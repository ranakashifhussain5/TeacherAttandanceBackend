<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ClassController extends Controller
{
    // List classes
    public function index()
    {
        $user = Auth::user();

        if($user->role === 'cr'){
            // CR sees only their classes in their program/batch (batch already implies shift)
            $classes = ClassRoom::with('teacher:id,name')
                ->where('cr_id', $user->id)
                ->where('program_id', $user->program_id)
                ->where('batch_id', $user->batch_id)
                ->get();
        } else {
            // HOD: sees all, optionally filtered for the program/batch/shift drill-down
            $query = ClassRoom::with('teacher:id,name');

            if (request()->filled('program_id')) {
                $query->where('program_id', request('program_id'));
            }
            if (request()->filled('batch_id')) {
                $query->where('batch_id', request('batch_id'));
            }
            if (request()->filled('shift_id')) {
                $query->whereHas('batch', function ($q) {
                    $q->where('shift_id', request('shift_id'));
                });
            }

            $classes = $query->get();
        }

        return response()->json($classes);
    }

    // Create new class
    public function store(Request $request)
    {
        $user = Auth::user();

        // If CR, auto assign program, batch, and cr_id
        if($user->role === 'cr'){
            $request->merge([
                'program_id' => $user->program_id,
                'batch_id' => $user->batch_id,
                'cr_id' => $user->id,
            ]);
        }

        $request->validate([
            'class_name'=>'required|string',
            'teacher_id'=>'required|exists:teachers,id',
            'day'=>'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time'=>'required',
            'end_time'=>'required',
            'room'=>'required|string',
            'cr_id'=>'required|exists:users,id',
            'program_id'=>'required|exists:programs,id',
            'batch_id'=> [
                'required',
                Rule::exists('batches', 'id')->where('program_id', $request->program_id),
            ],
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
        $classes = ClassRoom::with('teacher:id,name')
            ->where('cr_id', $user->id)
            ->where('program_id', $user->program_id)
            ->where('batch_id', $user->batch_id)
            ->where('day', $today)
            ->get();
    } else {
        // HOD sees all classes for today, optionally filtered
        $query = ClassRoom::with('teacher:id,name')->where('day', $today);

        if (request()->filled('program_id')) {
            $query->where('program_id', request('program_id'));
        }
        if (request()->filled('batch_id')) {
            $query->where('batch_id', request('batch_id'));
        }
        if (request()->filled('shift_id')) {
            $query->whereHas('batch', function ($q) {
                $q->where('shift_id', request('shift_id'));
            });
        }

        $classes = $query->get();
    }

    return response()->json($classes);
}

}
