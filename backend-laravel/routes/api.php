<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\QuizController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/users', function (Request $request) {
    return User::all();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', function (Request $request) {
        $user = $request->user()->load('teacher');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'last_name' => optional($user->teacher)->last_name,
            'first_name' => optional($user->teacher)->first_name,
            'gender' => optional($user->teacher)->gender,
            'education_stage' => optional($user->teacher)->education_stage,
            'subject' => optional($user->teacher)->subject,
        ]);
    });

    Route::get('/auth/profile', [ProfileController::class, 'show']);

    Route::post('/auth/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    });

    Route::put('/auth/profile/user', [ProfileController::class, 'updateUser']);
    Route::put('/auth/profile/teacher', [ProfileController::class, 'updateTeacher']);
    Route::delete('/auth/account', [ProfileController::class, 'deleteAccount']);

    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/classes/{classId}/students', [ClassController::class, 'students']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::put('/classes/{classId}', [ClassController::class, 'update']);
    Route::delete('/classes/{classId}', [ClassController::class, 'destroy']);

    Route::post('/students', [StudentController::class, 'store']);
    Route::post('/students/bulk', [StudentController::class, 'bulkStore']);
    Route::put('/students/{studentId}', [StudentController::class, 'update']);
    Route::delete('/students/{studentId}', [StudentController::class, 'destroy']);

    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::put('/quizzes/{quizId}', [QuizController::class, 'update']);
    Route::delete('/quizzes/{quizId}', [QuizController::class, 'destroy']);
    Route::get('/quizzes/{quizId}/items', [QuizController::class, 'items']);
    Route::post('/quizzes/{quizId}/items', [QuizController::class, 'addItem']);
    Route::put('/quizzes/{quizId}/items/{questionId}', [QuizController::class, 'updateItem']);
    Route::delete('/quizzes/{quizId}/items/{questionId}', [QuizController::class, 'removeItem']);
    Route::post('/quizzes/{quizId}/assignments', [QuizController::class, 'assignClasses']);
    Route::delete('/quizzes/{quizId}/assignments/{assignmentId}', [QuizController::class, 'removeAssignment']);
    Route::get('/quizzes/{quizId}/answer-sheet', [QuizController::class, 'generateAnswerSheet']);
});
