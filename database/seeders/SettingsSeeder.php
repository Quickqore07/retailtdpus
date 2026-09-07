<?php

namespace Database\Seeders;

use App\Models\Settings\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'key' => 'app_name',
                'value' => 'QuickQore',
                'active' => true,
            ],
            [
                'key' => 'app_timezone',
                'value' => 'UTC',
                'active' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'USD',
                'active' => true,
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'active' => true,
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i:s',
                'active' => true,
            ],
            [
                'key' => 'records_per_page',
                'value' => '10',
                'active' => true,
            ],
            [
                'key' => 'email_notifications',
                'value' => 'enabled',
                'active' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'disabled',
                'active' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
