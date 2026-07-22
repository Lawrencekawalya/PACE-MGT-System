<?php

namespace App\Services;

use App\InventoryItemType;
use App\Models\AcademicYear;
use App\Models\CatalogueImport;
use App\Models\Course;
use App\Models\Level;
use App\Models\Pace;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\Term;

class DataIntegrityService
{
    /** @return list<array{key: string, label: string, status: string, detail: string, issues: list<string>}> */
    public function validate(): array
    {
        $catalogue = $this->catalogue();
        $stock = $this->stockLedger();
        $unassigned = Student::query()->whereNull('teacher_id')->count();
        $activeYears = AcademicYear::query()->where('is_active', true)->count();
        $activeTerms = Term::query()->where('is_active', true)->count();

        return [
            $this->check('catalogue', 'PACE catalogue', $catalogue['issues'], $catalogue['detail']),
            $this->check('stock', 'Stock ledger', $stock['issues'], $stock['detail']),
            $this->check('student_ownership', 'Student teacher ownership', $unassigned === 0 ? [] : ["{$unassigned} student(s) have no supervising teacher."], "{$unassigned} unassigned"),
            $this->check('academic_year', 'Active academic year', $activeYears === 1 ? [] : ["Expected one active academic year; found {$activeYears}."], "{$activeYears} active"),
            $this->check('academic_term', 'Active academic term', $activeTerms === 1 ? [] : ["Expected one active academic term; found {$activeTerms}."], "{$activeTerms} active"),
        ];
    }

    /** @return array{detail: string, issues: list<string>, expected: array{levels: int, courses: int, paces: int}, actual: array{levels: int, courses: int, paces: int}, checksum: string|null} */
    public function catalogue(?CatalogueImport $import = null): array
    {
        $import ??= CatalogueImport::query()->where('status', 'committed')->latest('committed_at')->first();
        if ($import === null) {
            return [
                'detail' => 'No committed catalogue import', 'issues' => ['No committed catalogue import is available for reconciliation.'],
                'expected' => ['levels' => 0, 'courses' => 0, 'paces' => 0],
                'actual' => ['levels' => Level::query()->where('is_active', true)->count(), 'courses' => Course::query()->where('is_active', true)->count(), 'paces' => Pace::query()->where('is_active', true)->count()],
                'checksum' => null,
            ];
        }

        $expectedLevels = [];
        $expectedCourses = [];
        $expectedPaces = [];
        $expectedRequirements = [];
        foreach ($import->rows()->whereIn('status', ['valid', 'committed'])->cursor() as $row) {
            $data = $row->normalized_data;
            if (! is_array($data)) {
                continue;
            }
            $levelCode = (string) $data['level_code'];
            $courseCode = (string) $data['course_code'];
            $paces = array_values(array_map('strval', (array) $data['paces']));
            $expectedLevels[$levelCode] = true;
            $expectedCourses[$courseCode] = true;
            $expectedPaces[$courseCode] = array_values(array_unique([...($expectedPaces[$courseCode] ?? []), ...$paces,
            ]));
            $expectedRequirements["{$levelCode}|{$courseCode}"] = $paces;
        }

        $levels = Level::query()->where('is_active', true)->with([
            'curriculumRequirements' => fn ($query) => $query->where('is_active', true)->with(['course:id,code', 'paces:id,number']),
        ])->get()->keyBy('code');
        $courses = Course::query()->where('is_active', true)->with(['paces' => fn ($query) => $query->where('is_active', true)->orderBy('sequence_order')])->get()->keyBy('code');
        $issues = [];

        $this->compareKeys('level', array_keys($expectedLevels), array_values($levels->keys()->map(fn (mixed $key): string => (string) $key)->all()), $issues);
        $this->compareKeys('course', array_keys($expectedCourses), array_values($courses->keys()->map(fn (mixed $key): string => (string) $key)->all()), $issues);
        foreach ($expectedPaces as $courseCode => $expectedNumbers) {
            $course = $courses->get($courseCode);
            if ($course === null) {
                continue;
            }
            $actualNumbers = $course->paces->pluck('number')->all();
            if ($actualNumbers !== $expectedNumbers) {
                $issues[] = "Course {$courseCode} PACE sequence does not match the committed workbook.";
            }
        }
        foreach ($expectedRequirements as $key => $expectedNumbers) {
            [$levelCode, $courseCode] = explode('|', $key, 2);
            $level = $levels->get($levelCode);
            $requirement = $level?->curriculumRequirements->first(fn ($requirement): bool => $requirement->course->code === $courseCode);
            $actualNumbers = $requirement?->paces->sortBy('pivot.sequence_order')->pluck('number')->values()->all();
            if ($actualNumbers !== $expectedNumbers) {
                $issues[] = "Curriculum {$levelCode}/{$courseCode} sequence does not match the committed workbook.";
            }
        }

        $expectedPaceCount = collect($expectedPaces)->sum(fn (array $paces): int => count($paces));
        $actual = ['levels' => $levels->count(), 'courses' => $courses->count(), 'paces' => Pace::query()->where('is_active', true)->count()];
        $expected = ['levels' => count($expectedLevels), 'courses' => count($expectedCourses), 'paces' => $expectedPaceCount];

        return [
            'detail' => "Expected {$expected['levels']} levels, {$expected['courses']} courses and {$expected['paces']} PACEs; found {$actual['levels']}, {$actual['courses']} and {$actual['paces']}.",
            'issues' => array_values(array_unique($issues)), 'expected' => $expected, 'actual' => $actual, 'checksum' => $import->checksum,
        ];
    }

    /** @return array{detail: string, issues: list<string>} */
    public function stockLedger(): array
    {
        $balances = [];
        $issues = [];
        $movementCount = 0;
        foreach (StockMovement::query()->orderBy('inventory_item_id')->orderBy('id')->cursor() as $movement) {
            $movementCount++;
            $running = ($balances[$movement->inventory_item_id] ?? 0) + $movement->quantity;
            if ($running !== $movement->balance_after) {
                $issues[] = "Stock movement {$movement->id} records balance {$movement->balance_after}; calculated balance is {$running}.";
            }
            if ($running < 0) {
                $issues[] = "Stock movement {$movement->id} produces a negative balance.";
            }
            $balances[$movement->inventory_item_id] = $running;
        }
        $missingInventory = Pace::query()->where('is_active', true)
            ->whereDoesntHave('inventoryItems', fn ($query) => $query->where('item_type', InventoryItemType::PaceBooklet))
            ->count();
        if ($missingInventory > 0) {
            $issues[] = "{$missingInventory} active PACE(s) have no booklet inventory item.";
        }

        return ['detail' => "{$movementCount} movement(s) checked; {$missingInventory} inventory coverage gap(s).", 'issues' => $issues];
    }

    /** @param list<string> $expected
     * @param  list<string>  $actual
     * @param  list<string>  $issues
     */
    private function compareKeys(string $label, array $expected, array $actual, array &$issues): void
    {
        foreach (array_diff($expected, $actual) as $missing) {
            $issues[] = "Expected {$label} {$missing} is missing.";
        }
        foreach (array_diff($actual, $expected) as $extra) {
            $issues[] = "Unexpected active {$label} {$extra} is not in the committed workbook.";
        }
    }

    /** @param list<string> $issues
     * @return array{key: string, label: string, status: string, detail: string, issues: list<string>}
     */
    private function check(string $key, string $label, array $issues, string $detail): array
    {
        return ['key' => $key, 'label' => $label, 'status' => $issues === [] ? 'passed' : 'failed', 'detail' => $detail, 'issues' => $issues];
    }
}
