<?php

use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\ReportExportStatus;
use App\ReportFormat;
use App\ReportType;
use App\RoleName;
use App\Services\ReportExportGenerator;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
    Storage::fake('local');
});

test('normal filtered report export is generated and downloaded immediately', function () {
    Queue::fake();
    config(['reports.queue_threshold' => 1]);
    $fixture = createReportFixture();

    $response = $this->actingAs($fixture['teacher'])
        ->withHeader('X-Inertia', 'true')
        ->post(route('report-exports.store'), [
            'report_type' => 'student_progress', 'format' => 'csv',
            'academic_year_id' => $fixture['year']->id, 'course_id' => $fixture['course']->id,
        ]);

    $export = ReportExport::query()->sole();
    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('report-exports.download', $export));
    expect($export->user_id)->toBe($fixture['teacher']->id)
        ->and($export->filters)->toMatchArray(['academic_year_id' => $fixture['year']->id, 'course_id' => $fixture['course']->id])
        ->and($export->status)->toBe(ReportExportStatus::Completed)
        ->and($export->row_count)->toBe(1);
    Storage::disk('local')->assertExists($export->path);
    Queue::assertNothingPushed();
});

test('report above the configured row threshold is queued', function () {
    Queue::fake();
    config(['reports.queue_threshold' => 0]);
    $fixture = createReportFixture();

    $this->actingAs($fixture['teacher'])->post(route('report-exports.store'), [
        'report_type' => 'student_progress', 'format' => 'csv',
        'academic_year_id' => $fixture['year']->id, 'course_id' => $fixture['course']->id,
    ])->assertRedirect();

    $export = ReportExport::query()->sole();
    expect($export->status)->toBe(ReportExportStatus::Pending);
    Queue::assertPushed(GenerateReportExport::class, fn ($job) => $job->export->is($export));
});

test('CSV and XLSX jobs use the same report calculations and filters', function (string $format) {
    $fixture = createReportFixture();
    $export = ReportExport::factory()->create([
        'user_id' => $fixture['teacher']->id,
        'report_type' => ReportType::StudentProgress,
        'format' => ReportFormat::from($format),
        'filters' => ['academic_year_id' => $fixture['year']->id, 'course_id' => $fixture['course']->id],
    ]);

    app(ReportExportGenerator::class)->generate($export);
    $export->refresh();

    expect($export->status)->toBe(ReportExportStatus::Completed)
        ->and($export->row_count)->toBe(1)
        ->and($export->path)->not->toBeNull();
    Storage::disk('local')->assertExists($export->path);
    $contents = Storage::disk('local')->get($export->path);
    if ($format === 'csv') {
        expect($contents)->toContain('Admission number')->toContain('FICA-0001')->toContain('33.3');
    } else {
        $temporary = tempnam(sys_get_temp_dir(), 'report-test-');
        file_put_contents($temporary, $contents);
        $sheet = IOFactory::load($temporary)->getActiveSheet();
        unlink($temporary);
        expect($sheet->getCell('A2')->getValue())->toBe('FICA-0001')
            ->and((float) $sheet->getCell('I2')->getValue())->toBe(33.3);
    }
})->with(['csv', 'xlsx']);

test('completed export download is private to its requesting user', function () {
    $owner = createStaffWithRole(RoleName::Teacher);
    $other = createStaffWithRole(RoleName::Teacher);
    $export = ReportExport::factory()->create([
        'user_id' => $owner->id, 'status' => ReportExportStatus::Completed,
        'path' => 'report-exports/test.csv', 'original_filename' => 'test.csv',
        'completed_at' => now(), 'expires_at' => now()->addDay(),
    ]);
    Storage::disk('local')->put($export->path, "Name\nGrace\n");

    $this->actingAs($owner)->get(route('report-exports.download', $export))->assertOk();
    $this->actingAs($other)->get(route('report-exports.download', $export))->assertForbidden();
});

test('expired report files and records are pruned', function () {
    $export = ReportExport::factory()->create([
        'status' => ReportExportStatus::Completed, 'path' => 'report-exports/expired.csv',
        'expires_at' => now()->subMinute(),
    ]);
    Storage::disk('local')->put($export->path, 'expired');

    $this->artisan('reports:prune')->assertSuccessful();

    Storage::disk('local')->assertMissing($export->path);
    $this->assertDatabaseMissing('report_exports', ['id' => $export->id]);
});

test('storekeeper cannot export an academic report', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($storekeeper)->post(route('report-exports.store'), [
        'report_type' => 'student_progress', 'format' => 'csv',
    ])->assertForbidden();
});
