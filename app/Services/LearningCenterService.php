<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningCenterService
{
    /** @param array<string, mixed> $data */
    public function save(?LearningCenter $learningCenter, array $data): LearningCenter
    {
        $submittedLevelIds = $data['level_ids'] ?? [];
        $submittedTeacherIds = $data['teacher_ids'] ?? [];
        $levelIds = collect(is_array($submittedLevelIds) ? $submittedLevelIds : [])->map(fn ($id): int => (int) $id)->unique()->values();
        $teacherIds = collect(is_array($submittedTeacherIds) ? $submittedTeacherIds : [])->map(fn ($id): int => (int) $id)->unique()->values();

        $conflictingLevels = Level::query()
            ->whereKey($levelIds)
            ->whereNotNull('learning_center_id')
            ->when($learningCenter, fn ($query) => $query->where('learning_center_id', '!=', $learningCenter->id))
            ->orderBy('sort_order')
            ->pluck('name');

        if ($conflictingLevels->isNotEmpty()) {
            throw ValidationException::withMessages([
                'level_ids' => 'These grades already belong to another learning center: '.$conflictingLevels->implode(', ').'.',
            ]);
        }

        if ($learningCenter !== null) {
            $removedLevelIds = $learningCenter->levels()->whereNotIn('levels.id', $levelIds)->pluck('id');
            $activeEnrollmentCount = $learningCenter->studentEnrollments()
                ->where('status', EnrollmentStatus::Active)
                ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
                ->when(
                    $data['is_active'],
                    fn ($query) => $query->whereIn('level_id', $removedLevelIds),
                )
                ->count();

            if ($activeEnrollmentCount > 0) {
                throw ValidationException::withMessages([
                    $data['is_active'] ? 'level_ids' : 'is_active' => 'Move or close current student enrollments before removing their grade or deactivating this learning center.',
                ]);
            }
        }

        return DB::transaction(function () use ($learningCenter, $data, $levelIds, $teacherIds): LearningCenter {
            $learningCenter ??= new LearningCenter;
            $learningCenter->fill([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ])->save();

            $learningCenter->levels()->whereNotIn('levels.id', $levelIds)->update(['learning_center_id' => null]);
            Level::query()->whereKey($levelIds)->update(['learning_center_id' => $learningCenter->id]);
            StudentEnrollment::query()
                ->whereIn('level_id', $levelIds)
                ->whereNull('learning_center_id')
                ->update(['learning_center_id' => $learningCenter->id]);
            $learningCenter->teachers()->sync($teacherIds);

            return $learningCenter->load(['levels:id,learning_center_id,name,code,sort_order', 'teachers:id,name,email']);
        });
    }
}
