<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('teacher');

        return response()->json([
            'users' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'teachers' => [
                'teacher_id' => optional($user->teacher)->teacher_id,
                'user_id' => $user->id,
                'last_name' => optional($user->teacher)->last_name,
                'first_name' => optional($user->teacher)->first_name,
                'gender' => optional($user->teacher)->gender,
                'subject' => optional($user->teacher)->subject,
                'education_stage' => optional($user->teacher)->education_stage,
            ],
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $rules = [
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ];

        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = ['required', 'string', Password::default(), 'confirmed'];
        }

        $validated = $request->validate($rules);

        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['当前密码不正确'],
                ]);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name = $validated['username'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->save();

        return response()->json([
            'message' => '账号信息更新成功',
            'users' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    public function updateTeacher(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'last_name' => 'nullable|string|max:50',
            'first_name' => 'nullable|string|max:50',
            'gender' => 'nullable|string|in:男,女,male,female',
            'subject' => 'nullable|string|max:20',
            'education_stage' => 'nullable|string|in:小学,初中,高中,大学',
        ]);

        $normalizedGender = match ($validated['gender'] ?? null) {
            'male' => '男',
            'female' => '女',
            default => $validated['gender'] ?? null,
        };

        $teacher = Teacher::firstOrCreate(['user_id' => $user->id]);
        $teacher->last_name = $validated['last_name'] ?? null;
        $teacher->first_name = $validated['first_name'] ?? null;
        $teacher->gender = $normalizedGender;
        $teacher->education_stage = $validated['education_stage'] ?? null;
        $teacher->subject = $validated['subject'] ?? null;
        $teacher->save();

        $freshTeacher = $teacher->fresh();

        return response()->json([
            'message' => '教师信息更新成功',
            'teachers' => [
                'teacher_id' => $freshTeacher->teacher_id,
                'user_id' => $freshTeacher->user_id,
                'last_name' => $freshTeacher->last_name,
                'first_name' => $freshTeacher->first_name,
                'gender' => $freshTeacher->gender,
                'subject' => $freshTeacher->subject,
                'education_stage' => $freshTeacher->education_stage,
            ],
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // 删除用户账户
        // 由于数据库设置了级联删除(cascadeOnDelete)，删除user会自动删除：
        // 1. teachers 表中的记录
        // 2. teacher相关的questions、quizzes
        // 3. quiz相关的questions、quiz_assignments、submissions
        // 4. submission相关的answer_details
        $user->delete();

        // 注销会话
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => '账户已成功注销',
        ]);
    }
}
