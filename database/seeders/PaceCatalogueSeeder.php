<?php

namespace Database\Seeders;

use App\Models\CatalogueImport;
use App\Models\User;
use App\RoleName;
use App\Services\CatalogueImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PaceCatalogueSeeder extends Seeder
{
    public function run(CatalogueImportService $imports): void
    {
        $source = database_path('seeders/data/pace-details.xlsx');
        $contents = file_get_contents($source);
        if ($contents === false) {
            throw new RuntimeException('The baseline PACE workbook could not be read.');
        }

        $checksum = hash('sha256', $contents);
        if (CatalogueImport::query()->where('checksum', $checksum)->where('status', 'committed')->exists()) {
            return;
        }

        $administrator = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Administrator->value))
            ->firstOrFail();
        $path = 'catalogue-imports/baseline-'.Str::uuid().'.xlsx';
        Storage::disk('local')->put($path, $contents);

        $import = CatalogueImport::query()->create([
            'original_name' => 'PACE DETAILS.xlsx',
            'file_path' => $path,
            'checksum' => $checksum,
            'uploaded_by' => $administrator->id,
        ]);

        $imports->parse($import);
        $imports->commit($import->fresh(), $administrator);
    }
}
