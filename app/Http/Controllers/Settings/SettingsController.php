<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Setting;
use App\Models\Settings\SettingsItem;
use App\Http\Controllers\Controller;
use App\Services\DefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function getSettings()
    {
        $this->authorize('access', 'app-settings.update');
        
        // Don't return general settings unless authenticated
        $isAuthenticated = session('general_settings_authenticated', false);
        
        $settings = [];
        if ($isAuthenticated) {
            $dbSettings = Setting::all()->keyBy('key');
            $defaultSettings = DefaultSettings::schema();

            foreach($defaultSettings as $defaultSetting){
                if(!isset($dbSettings[$defaultSetting['key']])){
                    $settings[] = [
                        'key' => $defaultSetting['key'],
                        'value' => $defaultSetting['default_value'],
                        'value_type' => isset($defaultSetting['value_type']) ? $defaultSetting['value_type'] : 'text',
                        'display_key' => $this->formatKey($defaultSetting['key']),
                        'active' => true,
                        'min_value' => isset($defaultSetting['min_value']) ? $defaultSetting['min_value'] : null,
                        'max_value' => isset($defaultSetting['max_value']) ? $defaultSetting['max_value'] : null,
                        'description' => $defaultSetting['description'],
                    ];
                }else{
                    $settings[] = [
                        'key' => $dbSettings[$defaultSetting['key']]->key,
                        'value' => $dbSettings[$defaultSetting['key']]->value,
                        'value_type' => isset($dbSettings[$defaultSetting['key']]->value_type) ? $dbSettings[$defaultSetting['key']]->value_type : 'text',
                        'display_key' => $this->formatKey($dbSettings[$defaultSetting['key']]->key),
                        'active' => $dbSettings[$defaultSetting['key']]->active,
                        'min_value' => isset($dbSettings[$defaultSetting['key']]->min_value) ? $dbSettings[$defaultSetting['key']]->min_value : null,
                        'max_value' => isset($dbSettings[$defaultSetting['key']]->max_value) ? $dbSettings[$defaultSetting['key']]->max_value : null,
                        'description' => $dbSettings[$defaultSetting['key']]->description,
                    ];
                }
            }
        }
        
        $settingsItems = SettingsItem::orderBy('report_type', 'desc')->orderBy('difference_value_1', 'asc')->get();
        return to_json([
            'settings' => $settings,
            'settingsItems' => $settingsItems,
        ]);
    }
    
    public function verifyPassword(Request $request)
    {
        $this->authorize('access', 'app-settings.update');
        
        $request->validate([
            'password' => 'required|string',
        ]);
        
        // Get or initialize password attempts
        $attempts = session('general_settings_password_attempts', 0);
        
        // Check if password is correct
        $correctPassword = env('GENERAL_SETTINGS_PASSWORD', 'admin123');
        $isCorrectPassword = $request->password === $correctPassword;
        
        // First attempt always fails (as per requirement)
        if ($attempts === 0) {
            session(['general_settings_password_attempts' => 1]);
            return to_json([
                'success' => true,
                'authenticated' => false,
                'attempts' => 1,
                'message' => 'Incorrect password. Please try again.'
            ]);
        }
        
        // Second attempt - check actual password
        if ($attempts === 1) {
            if ($isCorrectPassword) {
                session(['general_settings_authenticated' => true]);
                session(['general_settings_password_attempts' => 0]); // Reset attempts
                return to_json([
                    'success' => true,
                    'authenticated' => true,
                    'attempts' => 2,
                    'message' => 'Password verified successfully'
                ]);
            } else {
                session(['general_settings_password_attempts' => 2]);
                return to_json([
                    'success' => true,
                    'authenticated' => false,
                    'attempts' => 2,
                    'message' => 'Incorrect password. Access denied.'
                ]);
            }
        }
        
        // More than 2 attempts - reset
        session(['general_settings_password_attempts' => 0]);
        return to_json([
            'success' => false,
            'authenticated' => false,
            'attempts' => 0,
            'message' => 'Too many attempts. Please try again.'
        ]);
    }
    
    public function getGeneralSettings()
    {
        $this->authorize('access', 'app-settings.update');
        
        // Check if authenticated
        if (!session('general_settings_authenticated', false)) {
            return to_json([
                'settings' => [],
                'message' => 'Not authenticated'
            ], 403);
        }
        
        $dbSettings = Setting::all()->keyBy('key');
        $defaultSettings = DefaultSettings::schema();

        $settings = [];
        foreach($defaultSettings as $defaultSetting){
            if(!isset($dbSettings[$defaultSetting['key']])){
                $settings[] = [
                    'key' => $defaultSetting['key'],
                    'value' => $defaultSetting['default_value'],
                    'value_type' => isset($defaultSetting['value_type']) ? $defaultSetting['value_type'] : 'text',
                    'display_key' => $this->formatKey($defaultSetting['key']),
                    'active' => true,
                    'min_value' => isset($defaultSetting['min_value']) ? $defaultSetting['min_value'] : null,
                    'max_value' => isset($defaultSetting['max_value']) ? $defaultSetting['max_value'] : null,
                    'description' => $defaultSetting['description'],
                ];
            }else{
                $settings[] = [
                    'key' => $dbSettings[$defaultSetting['key']]->key,
                    'value' => $dbSettings[$defaultSetting['key']]->value,
                    'value_type' => isset($dbSettings[$defaultSetting['key']]->value_type) ? $dbSettings[$defaultSetting['key']]->value_type : 'text',
                    'display_key' => $this->formatKey($dbSettings[$defaultSetting['key']]->key),
                    'active' => $dbSettings[$defaultSetting['key']]->active,
                    'min_value' => isset($dbSettings[$defaultSetting['key']]->min_value) ? $dbSettings[$defaultSetting['key']]->min_value : null,
                    'max_value' => isset($dbSettings[$defaultSetting['key']]->max_value) ? $dbSettings[$defaultSetting['key']]->max_value : null,
                    'description' => $dbSettings[$defaultSetting['key']]->description,
                ];
            }
        }
        
        return to_json([
            'settings' => $settings,
        ]);
    }
    
    private function formatKey($key)
    {

        $str= ucwords(str_replace(['_', '-'], ' ', $key));
        if(str_contains($str, ' Dc ')){
            return str_replace(' Dc ', ' DC ', $str);
        }
        if(str_contains($str, ' Pa ')){
            return str_replace(' Pa ', ' PA ', $str);
        }
        return $str;
    }
    
    public function updateSettings(Request $request)
    {
        $this->authorize('access', 'settings.update');
        
        DB::beginTransaction();
        try {
            // Only update general settings if authenticated
            if ($request->has('settings') && session('general_settings_authenticated', false)) {
                foreach ($request->settings as $setting) {
                    Setting::updateOrCreate(
                        ['key' => $setting['key']],
                        [
                            'value' => $setting['value'],
                            'value_type' => $setting['value_type'],
                            'min_value' => isset($setting['min_value']) ? $setting['min_value'] : null,
                            'max_value' => isset($setting['max_value']) ? $setting['max_value'] : null,
                        ]
                    );
                }
            }
            
            // Always allow updating settings items (report settings)
            if ($request->has('settingsItems')) {
                SettingsItem::query()->delete(); 
                SettingsItem::insert($request->settingsItems);
            }
            
            DB::commit();
            return to_json([
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSettingsItems(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
        ]);
        $settingsItems = SettingsItem::where('report_type', $request->report_type)->get();
        return to_json([
            'settingsItems' => $settingsItems,
        ]);
    }

}