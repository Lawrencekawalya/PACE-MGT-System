<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchoolSettingRequest;
use App\Models\SchoolSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function edit(): Response
    {
        $settings = SchoolSetting::current();
        Gate::authorize('view', $settings);

        return Inertia::render('admin/school-settings/Edit', [
            'settings' => [
                ...$settings->only([
                    'official_name',
                    'short_name',
                    'slogan',
                    'country_code',
                    'timezone',
                    'date_format',
                    'time_format',
                    'self_test_pass_mark',
                    'pace_test_pass_mark',
                    'self_test_retry_limit',
                ]),
                'logo_url' => $settings->logo_path === null ? null : Storage::disk('public')->url($settings->logo_path),
            ],
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request): RedirectResponse
    {
        $settings = SchoolSetting::current();
        Gate::authorize('update', $settings);
        $trackedFields = [
            'official_name', 'short_name', 'slogan', 'country_code', 'timezone',
            'date_format', 'time_format', 'logo_path', 'self_test_pass_mark', 'pace_test_pass_mark', 'self_test_retry_limit',
        ];
        $oldValues = $settings->only($trackedFields);
        $data = $request->safe()->except(['logo', 'remove_logo']);
        $oldLogo = $settings->logo_path;

        if ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('school-branding', 'public');
        }

        $settings->update($data);

        if ($oldLogo !== null && $oldLogo !== $settings->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        Cache::forget('school-settings.timezone');
        $this->activityLogger->record(
            $request->user(),
            'school-settings.updated',
            $settings,
            $oldValues,
            $settings->only($trackedFields),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'School settings updated.']);

        return back();
    }
}
