<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::query()->pluck('value', 'key')->all();

        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_from_address' => 'nullable|email|max:255',
            'domain' => 'nullable|url:http,https|max:255',
            'maintenance_mode' => 'required|in:on,off',
            'platform_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico|max:512',
        ]);

        if ($request->hasFile('platform_logo')) {
            $this->deletePreviousFile('platform_logo');
            $validated['platform_logo'] = $request->file('platform_logo')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            $this->deletePreviousFile('favicon');
            $validated['favicon'] = $request->file('favicon')->store('settings', 'public');
        }

        if (!empty($validated['smtp_password'])) {
            $validated['smtp_password'] = Crypt::encryptString($validated['smtp_password']);
        } else {
            unset($validated['smtp_password']);
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => in_array($key, ['platform_logo', 'favicon'], true) ? 'file' : 'text',
                    'group' => $this->groupFor($key),
                ]
            );
        }

        Cache::forget('platform.settings');

        if ($validated['maintenance_mode'] === 'on') {
            $secret = Setting::where('key', 'maintenance_secret')->value('value') ?: Str::random(40);

            Setting::updateOrCreate(
                ['key' => 'maintenance_secret'],
                ['value' => $secret, 'type' => 'text', 'group' => 'system']
            );

            Artisan::call('down', ['--render' => 'errors::503', '--secret' => $secret]);
        } else {
            Artisan::call('up');
        }

        return back()->with('success', 'Configuración actualizada correctamente.');
    }

    private function deletePreviousFile(string $key): void
    {
        $path = Setting::where('key', $key)->value('value');

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'smtp_') => 'mail',
            in_array($key, ['platform_logo', 'favicon'], true) => 'branding',
            $key === 'maintenance_mode' => 'system',
            default => 'general',
        };
    }
}
