<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'manage_users', 'module' => 'identity', 'description' => 'Quản lý tài khoản và trạng thái người dùng.'],
            ['name' => 'manage_roles', 'module' => 'identity', 'description' => 'Quản lý vai trò và quyền.'],
            ['name' => 'view_courses', 'module' => 'content', 'description' => 'Xem khóa học được cấp quyền.'],
            ['name' => 'manage_courses', 'module' => 'content', 'description' => 'Quản lý khóa học và nội dung.'],
            ['name' => 'manage_questions', 'module' => 'content', 'description' => 'Soạn và quản lý ngân hàng câu hỏi.'],
            ['name' => 'manage_exams', 'module' => 'exams', 'description' => 'Tạo và vận hành ca thi.'],
            ['name' => 'take_exams', 'module' => 'exams', 'description' => 'Tham gia ca thi được cấp quyền.'],
            ['name' => 'export_latex', 'module' => 'latex', 'description' => 'Xuất đề thi LaTeX.'],
            ['name' => 'use_ai_tutor', 'module' => 'ai', 'description' => 'Sử dụng gia sư AI ngoài ca thi.'],
            ['name' => 'manage_ai_settings', 'module' => 'ai', 'description' => 'Quản lý cấu hình và usage AI.'],
            ['name' => 'view_audit_logs', 'module' => 'operations', 'description' => 'Xem nhật ký audit trong thời hạn lưu trữ.'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(['name' => $permission['name']], $permission);
        }

        $admin = Role::query()->where('name', 'admin')->firstOrFail();
        $teacher = Role::query()->where('name', 'teacher')->firstOrFail();
        $student = Role::query()->where('name', 'student')->firstOrFail();

        $admin->permissions()->sync(Permission::query()->pluck('id'));
        $teacher->permissions()->sync(Permission::query()->whereIn('name', [
            'view_courses', 'manage_courses', 'manage_questions', 'manage_exams', 'export_latex', 'use_ai_tutor',
        ])->pluck('id'));
        $student->permissions()->sync(Permission::query()->whereIn('name', [
            'view_courses', 'take_exams', 'use_ai_tutor',
        ])->pluck('id'));
    }
}
