<?php

namespace App\Providers;

use App\Console\Commands\CreateDirectoryStructure;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->applyPlatformSettings();

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDirectoryStructure::class,
            ]);
        }
    }

    private function applyPlatformSettings(): void
    {
        try {
            $settings = Cache::remember(
                'platform.settings',
                now()->addHour(),
                fn () => Setting::query()->pluck('value', 'key')->all()
            );
        } catch (Throwable) {
            return;
        }

        config([
            'app.name' => $settings['app_name'] ?? config('app.name'),
            'app.url' => $settings['domain'] ?? config('app.url'),
            'platform.support_email' => $settings['support_email'] ?? null,
            'platform.logo_url' => !empty($settings['platform_logo'])
                ? Storage::disk('public')->url($settings['platform_logo'])
                : asset('assets/brand/cisma-fact.png'),
            'platform.icon_url' => !empty($settings['platform_logo'])
                ? Storage::disk('public')->url($settings['platform_logo'])
                : asset('assets/brand/cisma-fact-icon.png'),
            'platform.favicon_url' => !empty($settings['favicon'])
                ? Storage::disk('public')->url($settings['favicon'])
                : asset('assets/brand/favicon.png'),
        ]);

        if (empty($settings['smtp_host'])) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['smtp_host'],
            'mail.mailers.smtp.port' => (int) ($settings['smtp_port'] ?? 587),
            'mail.mailers.smtp.username' => $settings['smtp_username'] ?? null,
            'mail.mailers.smtp.password' => $this->decryptSetting($settings['smtp_password'] ?? null),
            'mail.mailers.smtp.scheme' => ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'smtps' : null,
            'mail.from.address' => $settings['smtp_from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['app_name'] ?? config('app.name'),
        ]);
    }

    private function decryptSetting(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
}
