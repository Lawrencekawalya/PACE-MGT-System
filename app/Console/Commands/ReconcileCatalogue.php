<?php

namespace App\Console\Commands;

use App\Models\CatalogueImport;
use App\Services\DataIntegrityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalogue:reconcile {import? : Committed catalogue import ID} {--json : Return machine-readable JSON}')]
#[Description('Compare the active catalogue with a committed workbook import')]
class ReconcileCatalogue extends Command
{
    public function handle(DataIntegrityService $integrity): int
    {
        $importId = $this->argument('import');
        $import = $importId === null
            ? null
            : CatalogueImport::query()->where('status', 'committed')->find($importId);

        if ($importId !== null && $import === null) {
            $this->error("Committed catalogue import {$importId} was not found.");

            return self::FAILURE;
        }

        $result = $integrity->catalogue($import);
        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Measure', 'Expected', 'Actual'], collect($result['expected'])->map(
                fn (int $expected, string $key): array => [ucfirst($key), $expected, $result['actual'][$key]],
            )->values()->all());
            $this->line('Workbook checksum: '.($result['checksum'] ?? 'not available'));
            foreach ($result['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        if ($result['issues'] !== []) {
            $this->error('Catalogue reconciliation failed.');

            return self::FAILURE;
        }

        $this->info('Catalogue reconciliation passed.');

        return self::SUCCESS;
    }
}
