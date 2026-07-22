<?php

namespace App\Services;

use App\InventoryItemType;
use App\Models\AcademicYear;
use App\Models\CatalogueImport;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    /** @return array{status: string, checked_at: string, checks: list<array{key: string, label: string, status: string, detail: string}>} */
    public function infrastructure(): array
    {
        $checks = [$this->databaseCheck(), $this->cacheCheck(), $this->storageCheck(), $this->schedulerCheck(), $this->queueCheck(), $this->environmentCheck()];

        return [
            'status' => collect($checks)->contains('status', 'failed')
                ? 'unhealthy'
                : (collect($checks)->contains('status', 'warning') ? 'degraded' : 'healthy'),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /** @return list<array{key: string, label: string, status: string, detail: string}> */
    public function releaseChecks(): array
    {
        $activeYears = AcademicYear::query()->where('is_active', true)->count();
        $activeTerms = Term::query()->where('is_active', true)->count();
        $unassignedStudents = Student::query()->whereNull('teacher_id')->count();
        $paceCount = Pace::query()->where('is_active', true)->count();
        $missingInventory = Pace::query()->where('is_active', true)
            ->whereDoesntHave('inventoryItems', fn ($query) => $query->where('item_type', InventoryItemType::PaceBooklet))
            ->count();
        $committedImports = CatalogueImport::query()->where('status', 'committed')->count();

        return [
            $this->releaseCheck('academic_year', 'Active academic year', $activeYears === 1, "{$activeYears} active"),
            $this->releaseCheck('term', 'Active academic term', $activeTerms === 1, "{$activeTerms} active"),
            $this->releaseCheck('student_ownership', 'Student teacher ownership', $unassignedStudents === 0, "{$unassignedStudents} unassigned"),
            $this->releaseCheck('catalogue', 'Committed PACE catalogue', $committedImports > 0 && $paceCount > 0, "{$committedImports} import(s), {$paceCount} active PACEs"),
            $this->releaseCheck('inventory_coverage', 'PACE inventory coverage', $missingInventory === 0 && InventoryItem::query()->exists(), "{$missingInventory} active PACEs missing inventory"),
        ];
    }

    public function isReady(): bool
    {
        return collect($this->infrastructure()['checks'])
            ->whereIn('key', ['database', 'cache', 'storage'])
            ->doesntContain('status', 'failed');
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return $this->check('database', 'Database', 'passed', 'Connection available');
        } catch (Throwable) {
            return $this->check('database', 'Database', 'failed', 'Connection unavailable');
        }
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function cacheCheck(): array
    {
        $key = 'health:'.Str::uuid();
        try {
            Cache::put($key, 'ok', 10);
            $available = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $this->check('cache', 'Cache', $available ? 'passed' : 'failed', $available ? 'Read and write available' : 'Read-back failed');
        } catch (Throwable) {
            return $this->check('cache', 'Cache', 'failed', 'Read and write unavailable');
        }
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function storageCheck(): array
    {
        $disk = (string) config('operations.backup_disk');
        $path = 'health/'.Str::uuid().'.txt';
        try {
            $stored = Storage::disk($disk)->put($path, 'ok');
            $available = $stored && Storage::disk($disk)->get($path) === 'ok';
            Storage::disk($disk)->delete($path);

            return $this->check('storage', 'Private storage', $available ? 'passed' : 'failed', $available ? "Disk {$disk} available" : "Disk {$disk} read-back failed");
        } catch (Throwable) {
            return $this->check('storage', 'Private storage', 'failed', "Disk {$disk} unavailable");
        }
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function schedulerCheck(): array
    {
        $lastRun = Cache::get('system:scheduler:last-run');
        if (! is_string($lastRun)) {
            return $this->check('scheduler', 'Scheduler', 'warning', 'No heartbeat recorded');
        }

        $age = now()->diffInMinutes($lastRun);
        $fresh = $age <= (int) config('operations.scheduler_grace_minutes');

        return $this->check('scheduler', 'Scheduler', $fresh ? 'passed' : 'warning', $fresh ? 'Heartbeat is current' : "Last heartbeat {$age} minutes ago");
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function queueCheck(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $lastRun = Cache::get('system:queue:last-run');
            if (! is_string($lastRun)) {
                return $this->check('queue', 'Queue', 'warning', "No worker heartbeat; {$pending} pending, {$failed} failed");
            }
            $fresh = now()->diffInMinutes($lastRun) <= (int) config('operations.scheduler_grace_minutes');
            $status = $failed > 0 || ! $fresh ? 'warning' : 'passed';

            return $this->check('queue', 'Queue', $status, ($fresh ? 'Worker heartbeat is current' : 'Worker heartbeat is stale')."; {$pending} pending, {$failed} failed");
        } catch (Throwable) {
            return $this->check('queue', 'Queue', 'warning', 'Queue tables unavailable');
        }
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function environmentCheck(): array
    {
        $unsafe = app()->isProduction() && (bool) config('app.debug');

        return $this->check('environment', 'Environment', $unsafe ? 'failed' : 'passed', $unsafe ? 'Debug mode is enabled in production' : app()->environment());
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function releaseCheck(string $key, string $label, bool $passed, string $detail): array
    {
        return $this->check($key, $label, $passed ? 'passed' : 'warning', $detail);
    }

    /** @return array{key: string, label: string, status: string, detail: string} */
    private function check(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }
}
