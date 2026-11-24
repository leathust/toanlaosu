<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'audit_retention_months' => ['value' => 6],
            'exam_retention_years' => ['value' => 1],
            'ai_vector_search_enabled' => ['value' => false],
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], $value);
        }
    }
}
