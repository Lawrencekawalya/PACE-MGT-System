<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPeriodService
{
    /** @param array<string, mixed> $data */
    public function saveYear(?AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data): AcademicYear {
            if ($data['is_active']) {
                AcademicYear::query()->where('is_active', true)
                    ->when($academicYear, fn ($query) => $query->whereKeyNot($academicYear->getKey()))
                    ->update(['is_active' => false]);
            }

            $academicYear ??= new AcademicYear;
            $academicYear->fill($data)->save();

            if (! $academicYear->is_active) {
                $academicYear->terms()->where('is_active', true)->update(['is_active' => false]);
            }

            return $academicYear;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveTerm(AcademicYear $academicYear, ?Term $term, array $data): Term
    {
        if ($data['starts_on'] < $academicYear->starts_on->toDateString()
            || $data['ends_on'] > $academicYear->ends_on->toDateString()) {
            throw ValidationException::withMessages([
                'starts_on' => 'Term dates must fall within the academic year.',
            ]);
        }

        if ($data['is_active'] && ! $academicYear->is_active) {
            throw ValidationException::withMessages([
                'is_active' => 'Activate the academic year before activating one of its terms.',
            ]);
        }

        return DB::transaction(function () use ($academicYear, $term, $data): Term {
            if ($data['is_active']) {
                Term::query()->where('is_active', true)
                    ->when($term, fn ($query) => $query->whereKeyNot($term->getKey()))
                    ->update(['is_active' => false]);
            }

            $term ??= new Term;
            $term->academicYear()->associate($academicYear);
            $term->fill($data)->save();

            return $term;
        });
    }
}
