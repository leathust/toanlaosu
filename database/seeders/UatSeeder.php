<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UatSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(E2eLearningSeeder::class);
        $password = (string) env('UAT_PASSWORD', 'password');
        $student = $this->user('uat.student@example.com', 'UAT Student', $password);
        $teacher = $this->user('uat.teacher@example.com', 'UAT Teacher', $password);
        $admin = $this->user('uat.admin@example.com', 'UAT Admin', $password);
        $unverified = $this->user('uat.unverified@example.com', 'UAT Unverified', $password);
        $unverified->forceFill(['email_verified_at' => null])->save();
        $this->assignRole($student, 'student');
        $this->assignRole($teacher, 'teacher');
        $this->assignRole($admin, 'admin');
        $this->assignRole($unverified, 'student');

        $course = Course::query()->where('slug', 'e2e-radar')->firstOrFail();
        $course->teachers()->syncWithoutDetaching([$teacher->id => ['scope' => 'owner', 'assigned_by' => $admin->id, 'assigned_at' => now(), 'revoked_at' => null]]);
        Enrollment::query()->firstOrCreate(['course_id' => $course->id, 'user_id' => $student->id, 'period_number' => 1], [
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addYear(), 'source' => 'uat',
        ]);
    }

    private function user(string $email, string $name, string $password): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name, 'status' => 'active', 'timezone' => 'Asia/Ho_Chi_Minh',
            'email_verified_at' => now(), 'password' => Hash::make($password),
        ]);
    }

    private function assignRole(User $user, string $name): void
    {
        $role = Role::query()->firstOrCreate(['name' => $name], ['display_name' => ucfirst($name)]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
