<?php
use App\Http\Controllers\ClassController; 
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {


     Route::post('/logout', [AuthController::class,'logout']);
    Route::get('/attendances', [AttendanceController::class, 'index']);


     // List classes
    Route::get('/classes', [ClassController::class,'index']);

    //is ma jo os din ki classes ho gi wohi show ho gi
    Route::get('/classes/today', [ClassController::class,'todayClasses']);

    // Create / Update / Delete classes (CR/Admin)
    Route::post('/classes', [ClassController::class,'store']);
    Route::put('/classes/{id}', [ClassController::class,'update']);
    Route::delete('/classes/{id}', [ClassController::class,'destroy']);

     Route::get('/attendance', [AttendanceController::class,'index']);

    // Mark teacher arrived
    Route::post('/attendance/arrived', [AttendanceController::class,'markArrived']);

    // Mark teacher left
    Route::post('/attendance/left', [AttendanceController::class,'markLeft']);

    Route::get('/reports', [ReportsController::class, 'index']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
