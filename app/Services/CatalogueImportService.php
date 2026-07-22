<?php

namespace App\Services;

use App\Models\CatalogueImport;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\Pace;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class CatalogueImportService
{
    public function parse(CatalogueImport $import): void
    {
        $import->update(['status' => 'validating', 'failure_reason' => null]);
        $import->rows()->delete();

        try {
            $sheet = IOFactory::load(Storage::disk('local')->path($import->file_path))->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $level = null;
            $defaultRange = null;
            $section = null;
            $seen = [];
            $counts = ['valid' => 0, 'warning' => 0, 'invalid' => 0];

            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber === 1 || $this->isEmpty($row)) {
                    continue;
                }

                $columnA = $this->clean($row['A'] ?? null);
                $columnB = $this->clean($row['B'] ?? null);
                $courseName = $this->clean($row['C'] ?? null);
                $columnD = $this->clean($row['D'] ?? null);

                if ($courseName === null && $columnA !== null) {
                    $section = strtoupper($columnA);

                    continue;
                }

                if ($columnA !== null) {
                    $level = $this->normalizeLevel($columnA, $section);
                    $defaultRange = $columnB;
                }

                if ($courseName === null) {
                    continue;
                }

                $raw = ['level' => $columnA, 'default_range' => $columnB, 'course' => $courseName, 'course_range' => $columnD];
                $errors = [];
                $range = $columnD ?? $defaultRange;
                $normalized = null;
                $status = 'valid';

                if ($level === null) {
                    $errors[] = 'A level heading is required before this course.';
                }

                $sequence = $range === null ? null : $this->expandRange($range);
                if ($sequence === null) {
                    $errors[] = 'PACE range is missing or ambiguous.';
                }

                $normalizedCourse = $this->normalizeCourse($courseName);
                $duplicateKey = implode('|', [$level, $normalizedCourse, $range]);
                if (isset($seen[$duplicateKey])) {
                    $status = 'warning';
                    $errors[] = "Duplicate of spreadsheet row {$seen[$duplicateKey]}; it will be skipped.";
                } else {
                    $seen[$duplicateKey] = $rowNumber;
                }

                if ($errors !== [] && $status !== 'warning') {
                    $status = 'invalid';
                }

                if ($status !== 'invalid' && $status !== 'warning') {
                    $normalized = [
                        'level' => $level,
                        'level_code' => $this->levelCode($level),
                        'subject' => $this->subjectFor($normalizedCourse),
                        'course' => $normalizedCourse,
                        'course_code' => Str::upper(Str::slug($normalizedCourse)),
                        'range' => $range,
                        'paces' => $sequence,
                        'is_required' => $section !== 'ELECTIVES',
                    ];
                }

                $import->rows()->create([
                    'row_number' => $rowNumber,
                    'raw_data' => $raw,
                    'normalized_data' => $normalized,
                    'status' => $status,
                    'errors' => $errors ?: null,
                ]);
                $counts[$status]++;
            }

            $import->update([
                'status' => $counts['invalid'] === 0 ? 'ready' : 'failed',
                'valid_rows' => $counts['valid'],
                'warning_rows' => $counts['warning'],
                'invalid_rows' => $counts['invalid'],
                'failure_reason' => $counts['invalid'] === 0 ? null : 'Resolve invalid rows before committing this import.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $import->update(['status' => 'failed', 'failure_reason' => 'The workbook could not be read.']);
        }
    }

    /** @return array{created: int, updated: int, skipped: int} */
    public function commit(CatalogueImport $import, User $actor): array
    {
        if ($import->status === 'committed') {
            return ['created' => 0, 'updated' => 0, 'skipped' => $import->skipped_records];
        }

        if ($import->status !== 'ready' || $import->invalid_rows > 0) {
            throw ValidationException::withMessages(['import' => 'Only a validated import with no invalid rows can be committed.']);
        }

        return DB::transaction(function () use ($import, $actor): array {
            $counts = ['created' => 0, 'updated' => 0, 'skipped' => $import->warning_rows];
            $levelSort = Level::query()->max('sort_order') ?? 0;

            foreach ($import->rows()->where('status', 'valid')->cursor() as $row) {
                $data = $row->normalized_data;
                if (! is_array($data)) {
                    continue;
                }

                $level = Level::query()->firstOrCreate(
                    ['code' => $data['level_code']],
                    ['name' => $data['level'], 'sort_order' => ++$levelSort, 'is_active' => true],
                );
                $this->countChange($level->wasRecentlyCreated, $level->wasChanged(), $counts);

                $subjectCode = Str::upper(Str::slug($data['subject']));
                $subject = Subject::query()->firstOrCreate(
                    ['code' => $subjectCode],
                    ['name' => $data['subject'], 'is_active' => true],
                );
                $this->countChange($subject->wasRecentlyCreated, $subject->wasChanged(), $counts);

                $course = Course::query()->updateOrCreate(
                    ['code' => $data['course_code']],
                    ['subject_id' => $subject->id, 'name' => $data['course'], 'edition' => '', 'is_pace_course' => true, 'is_active' => true],
                );
                $this->countChange($course->wasRecentlyCreated, $course->wasChanged(), $counts);

                $requirement = CurriculumRequirement::query()->firstOrNew([
                    'level_id' => $level->id,
                    'course_id' => $course->id,
                ]);
                if (! $requirement->exists) {
                    $currentOrder = CurriculumRequirement::query()->where('level_id', $level->id)->max('sort_order');
                    $requirement->sort_order = max(0, (int) ($currentOrder ?? 0)) + 1;
                }
                $requirement->fill(['is_required' => $data['is_required'], 'is_active' => true])->save();
                $this->countChange($requirement->wasRecentlyCreated, $requirement->wasChanged(), $counts);

                $paceSync = [];
                foreach ($data['paces'] as $position => $number) {
                    $pace = Pace::query()->updateOrCreate(
                        ['course_id' => $course->id, 'number' => $number, 'edition' => ''],
                        ['sequence_order' => $this->sequenceOrder($number), 'is_active' => true],
                    );
                    $this->countChange($pace->wasRecentlyCreated, $pace->wasChanged(), $counts);
                    $paceSync[$pace->id] = ['sequence_order' => $position + 1];
                }
                $requirement->paces()->detach();
                $requirement->paces()->attach($paceSync);
                $row->update(['status' => 'committed']);
            }

            $import->update([
                'status' => 'committed',
                'committed_by' => $actor->id,
                'committed_at' => now(),
                'created_records' => $counts['created'],
                'updated_records' => $counts['updated'],
                'skipped_records' => $counts['skipped'],
            ]);

            return $counts;
        });
    }

    /** @return array<int, string>|null */
    public function expandRange(string $range): ?array
    {
        $normalized = strtoupper(trim($range));
        if (! preg_match('/^([A-Z]*)(\d+)\s*-\s*([A-Z]*)(\d+)(?:\s*\(ONLY EVEN NUMBERS\))?$/', $normalized, $matches)) {
            return null;
        }

        $prefix = $matches[1] !== '' ? $matches[1] : $matches[3];
        if ($matches[1] !== '' && $matches[3] !== '' && $matches[1] !== $matches[3]) {
            return null;
        }

        $start = (int) $matches[2];
        $end = (int) $matches[4];
        if ($start > $end || $end - $start > 500) {
            return null;
        }

        $step = str_contains($normalized, 'ONLY EVEN NUMBERS') ? 2 : 1;
        $width = max(strlen($matches[2]), strlen($matches[4]));
        $values = [];
        for ($number = $start; $number <= $end; $number += $step) {
            $values[] = $prefix.$this->formatNumber($number, $prefix, $width);
        }

        return $values;
    }

    /** @param array{created: int, updated: int, skipped: int} $counts */
    private function countChange(bool $created, bool $updated, array &$counts): void
    {
        $counts[$created ? 'created' : ($updated ? 'updated' : 'skipped')]++;
    }

    /** @param array<string, mixed> $row */
    private function isEmpty(array $row): bool
    {
        return collect($row)->filter(fn ($value) => $this->clean($value) !== null)->isEmpty();
    }

    private function clean(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : preg_replace('/\s+/', ' ', $value);
    }

    private function normalizeLevel(string $value, ?string $section): string
    {
        $value = strtoupper($value);
        if ($value === 'GRADE 11' || $section === 'ADVANCED' || $section === 'ELECTIVES') {
            return 'Advanced';
        }

        return Str::title($value);
    }

    private function levelCode(string $level): string
    {
        return $level === 'Advanced' ? 'ADV' : Str::upper(Str::replace('GRADE ', 'G', $level));
    }

    private function normalizeCourse(string $course): string
    {
        $course = preg_replace('/\s+/', ' ', trim($course));

        return match (strtoupper($course)) {
            'AMERICAN HISTORY (SST 1)' => 'American History (SST 1)',
            default => Str::title(strtolower($course)),
        };
    }

    private function subjectFor(string $course): string
    {
        $name = strtoupper($course);

        return match (true) {
            str_contains($name, 'MATH'), str_contains($name, 'ALGEBRA'), str_contains($name, 'GEOMETRY') => 'Mathematics',
            str_contains($name, 'SCIENCE'), str_contains($name, 'BIOLOGY'), str_contains($name, 'CHEMISTRY'), str_contains($name, 'PHYSICS'), str_contains($name, 'ASTRONOMY'), str_contains($name, 'HEALTH'), str_contains($name, 'NUTRITION') => 'Science',
            str_contains($name, 'SST'), str_contains($name, 'HISTORY'), str_contains($name, 'GEOGRAPHY'), str_contains($name, 'CIVICS'), str_contains($name, 'ECONOMICS'), str_contains($name, 'COLLECTIVISM'), str_contains($name, 'CONSTITUTION') => 'Social Studies',
            str_contains($name, 'ENGLISH'), str_contains($name, 'LITERATURE'), str_contains($name, 'WORD BUILDING'), str_contains($name, 'ETYMOLOGY') => 'English Language Arts',
            str_contains($name, 'BIBLE'), str_contains($name, 'BIBLICAL'), str_contains($name, 'CHRISTIAN'), str_contains($name, 'APOLOGETICS'), str_contains($name, 'LIVING') => 'Biblical Studies',
            default => $course,
        };
    }

    private function formatNumber(int $number, string $prefix, int $width): string
    {
        return $prefix === '' ? (string) $number : str_pad((string) $number, $width, '0', STR_PAD_LEFT);
    }

    private function sequenceOrder(string $number): int
    {
        preg_match('/\d+/', $number, $matches);

        return (int) ($matches[0] ?? 0);
    }
}
