<?php

namespace Database\Factories;

use App\Models\ReportExport;
use App\Models\User;
use App\ReportExportStatus;
use App\ReportFormat;
use App\ReportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_type' => ReportType::StudentProgress,
            'format' => ReportFormat::Csv,
            'filters' => [],
            'status' => ReportExportStatus::Pending,
            'disk' => 'local',
            'expires_at' => now()->addDays(7),
        ];
    }
}
