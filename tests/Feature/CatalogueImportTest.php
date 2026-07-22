<?php

use App\Models\CatalogueImport;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\Pace;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
    Storage::fake('local');
});

function paceWorkbook(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'PACE DETAILS.xlsx',
        file_get_contents(database_path('seeders/data/pace-details.xlsx')),
    );
}

test('supplied workbook is staged with approved normalization and can be committed', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $response = $this->actingAs($administrator)->post(route('admin.catalogue-imports.store'), ['workbook' => paceWorkbook()]);
    $import = CatalogueImport::query()->sole();
    $response->assertRedirect(route('admin.catalogue-imports.show', $import));

    expect($import->status)->toBe('ready')
        ->and($import->invalid_rows)->toBe(0)
        ->and($import->warning_rows)->toBe(1)
        ->and($import->rows()->where('row_number', 65)->value('normalized_data')['subject'])->toBe('Social Studies')
        ->and($import->rows()->where('row_number', 66)->value('status'))->toBe('warning');

    $this->actingAs($administrator)->post(route('admin.catalogue-imports.commit', $import))->assertRedirect();

    expect($import->fresh()->status)->toBe('committed')
        ->and(Level::query()->where('name', 'Advanced')->count())->toBe(1)
        ->and(Level::query()->where('name', 'Grade 11')->exists())->toBeFalse()
        ->and(Course::query()->where('name', 'Literature 8')->sole()->paces()->pluck('number')->all())->toBe(['86', '88', '90', '92', '94', '96'])
        ->and(CurriculumRequirement::query()->count())->toBeGreaterThan(0);
});

test('reimporting the supplied workbook is idempotent', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($administrator)->post(route('admin.catalogue-imports.store'), ['workbook' => paceWorkbook()]);
        $this->actingAs($administrator)->post(route('admin.catalogue-imports.commit', CatalogueImport::query()->latest('id')->firstOrFail()))->assertRedirect();
    }

    expect(Pace::query()->select(['course_id', 'number', 'edition'])->distinct()->count())->toBe(Pace::query()->count())
        ->and(Level::query()->where('name', 'Advanced')->count())->toBe(1);
});

test('teachers cannot upload catalogue workbooks', function () {
    $this->actingAs(createStaffWithRole(RoleName::Teacher))
        ->post(route('admin.catalogue-imports.store'), ['workbook' => paceWorkbook()])
        ->assertForbidden();
});
