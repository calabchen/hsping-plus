<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;

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
});
