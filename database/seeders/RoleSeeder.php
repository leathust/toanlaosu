<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Toàn quyền vận hành hệ thống.'],
            ['name' => 'teacher', 'display_name' => 'Giáo viên', 'description' => 'Quản lý nội dung trong phạm vi được phân công.'],
            ['name' => 'student', 'display_name' => 'Học sinh', 'description' => 'Học nội dung và làm bài được cấp quyền.'],
        ] as $role) {
            Role::query()->updateOrCreate(
                ['name' => $role['name']],
                [...$role, 'is_system' => true],
            );
        }
    }
}
