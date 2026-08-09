<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $gradeEightId = DB::table('levels')->where('name', 'Grade 8')->value('id');
            $gradeNineId = DB::table('levels')->where('name', 'Grade 9')->value('id');
            $courseId = DB::table('courses')->where('code', 'PHYSICAL-SCIENCE-1')->value('id');

            if ($gradeEightId === null || $gradeNineId === null || $courseId === null) {
                return;
            }

            $this->renumberPaces((int) $courseId, 1085, 1097);
            $this->moveRequirement((int) $courseId, (int) $gradeEightId, (int) $gradeNineId, 7);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $gradeEightId = DB::table('levels')->where('name', 'Grade 8')->value('id');
            $gradeNineId = DB::table('levels')->where('name', 'Grade 9')->value('id');
            $courseId = DB::table('courses')->where('code', 'PHYSICAL-SCIENCE-1')->value('id');

            if ($gradeEightId === null || $gradeNineId === null || $courseId === null) {
                return;
            }

            $this->renumberPaces((int) $courseId, 1097, 1085);
            $this->moveRequirement((int) $courseId, (int) $gradeNineId, (int) $gradeEightId, 8);
            $this->compactSortOrders((int) $gradeNineId, 7);
        });
    }

    private function renumberPaces(int $courseId, int $from, int $to): void
    {
        $sourceNumbers = range($from, $from + 11);
        $targetNumbers = range($to, $to + 11);
        $sourcePaces = DB::table('paces')
            ->where('course_id', $courseId)
            ->whereIn('number', array_map('strval', $sourceNumbers))
            ->orderBy('sequence_order')
            ->get(['id', 'number']);
        $targetCount = DB::table('paces')
            ->where('course_id', $courseId)
            ->whereIn('number', array_map('strval', $targetNumbers))
            ->count();

        if ($sourcePaces->isEmpty()) {
            return;
        }

        if ($sourcePaces->count() !== 12 || $targetCount > 0) {
            throw new RuntimeException('Physical Science 1 has a partial or conflicting PACE sequence.');
        }

        foreach ($sourcePaces as $index => $pace) {
            $oldNumber = (string) $pace->number;
            $newNumber = (string) $targetNumbers[$index];

            DB::table('paces')->where('id', $pace->id)->update([
                'number' => $newNumber,
                'sequence_order' => $targetNumbers[$index],
                'updated_at' => now(),
            ]);
            DB::table('inventory_items')
                ->where('pace_id', $pace->id)
                ->where('sku', "PACE-{$oldNumber}-{$pace->id}")
                ->update([
                    'sku' => "PACE-{$newNumber}-{$pace->id}",
                    'updated_at' => now(),
                ]);
        }
    }

    private function moveRequirement(int $courseId, int $fromLevelId, int $toLevelId, int $sortOrder): void
    {
        $requirement = DB::table('curriculum_requirements')
            ->where('level_id', $fromLevelId)
            ->where('course_id', $courseId)
            ->first(['id']);

        if ($requirement === null) {
            return;
        }

        if (DB::table('curriculum_requirements')->where('level_id', $toLevelId)->where('course_id', $courseId)->exists()) {
            throw new RuntimeException('Physical Science 1 already has a conflicting curriculum requirement.');
        }

        $this->makeSortOrderAvailable($toLevelId, $sortOrder);

        DB::table('curriculum_requirements')->where('id', $requirement->id)->update([
            'level_id' => $toLevelId,
            'sort_order' => $sortOrder,
            'is_required' => true,
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }

    private function makeSortOrderAvailable(int $levelId, int $sortOrder): void
    {
        DB::table('curriculum_requirements')
            ->where('level_id', $levelId)
            ->where('sort_order', '>=', $sortOrder)
            ->orderByDesc('sort_order')
            ->get(['id', 'sort_order'])
            ->each(function (object $requirement): void {
                DB::table('curriculum_requirements')->where('id', $requirement->id)->update([
                    'sort_order' => ((int) $requirement->sort_order) + 1,
                    'updated_at' => now(),
                ]);
            });
    }

    private function compactSortOrders(int $levelId, int $removedSortOrder): void
    {
        DB::table('curriculum_requirements')
            ->where('level_id', $levelId)
            ->where('sort_order', '>', $removedSortOrder)
            ->orderBy('sort_order')
            ->get(['id', 'sort_order'])
            ->each(function (object $requirement): void {
                DB::table('curriculum_requirements')->where('id', $requirement->id)->update([
                    'sort_order' => ((int) $requirement->sort_order) - 1,
                    'updated_at' => now(),
                ]);
            });
    }
};
