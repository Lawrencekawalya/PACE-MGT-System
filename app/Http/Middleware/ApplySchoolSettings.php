<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplySchoolSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('school_settings')) {
            $timezone = Cache::rememberForever(
                'school-settings.timezone',
                fn (): string => SchoolSetting::query()->value('timezone') ?? config('app.timezone'),
            );

            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
