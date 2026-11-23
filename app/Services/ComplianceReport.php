<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Sop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Satu-satunya tempat yang menghitung kepatuhan baca.
 * Aturan: konfirmasi karyawan untuk VERSI YANG BERLAKU pada SOP berstatus published.
 */
class ComplianceReport
{
    public function employeeCount(): int
    {
        return User::query()->where('role', UserRole::Employee->value)->count();
    }

    /**
     * Daftar departemen karyawan, untuk filter.
     *
     * @return array<int, string>
     */
    public function departments(): array
    {
        return User::query()
            ->where('role', UserRole::Employee->value)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->all();
    }

    /**
     * Kepatuhan tiap SOP terbit. Tiap SOP mendapat atribut tambahan:
     * acknowledged_count, employee_count, dan percentage.
     *
     * @return Collection<int, Sop>
     */
    public function perSop(): Collection
    {
        $employees = $this->employeeCount();

        return Sop::query()
            ->published()
            ->withCount([
                'acknowledgments as acknowledged_count' => fn ($query) => $query
                    ->whereColumn('sop_acknowledgments.sop_version', 'sops.version')
                    ->whereIn('user_id', $this->employeeIds()),
            ])
            ->orderBy('document_number')
            ->get()
            ->each(function (Sop $sop) use ($employees) {
                $sop->employee_count = $employees;
                $sop->percentage = $this->percentage($sop->acknowledged_count, $employees);
            });
    }

    /**
     * Ringkasan seluruh SOP terbit.
     *
     * @return array{employees: int, sops: int, expected: int, acknowledged: int, percentage: int}
     */
    public function overall(Collection $perSop): array
    {
        $employees = $this->employeeCount();
        $expected = $employees * $perSop->count();
        $acknowledged = (int) $perSop->sum('acknowledged_count');

        return [
            'employees' => $employees,
            'sops' => $perSop->count(),
            'expected' => $expected,
            'acknowledged' => $acknowledged,
            'percentage' => $this->percentage($acknowledged, $expected),
        ];
    }

    /**
     * Ringkasan satu SOP: berapa yang sudah, berapa yang masih memegang versi lama, berapa yang belum.
     *
     * @return array{employees: int, acknowledged: int, outdated: int, missing: int, percentage: int}
     */
    public function summaryFor(Sop $sop): array
    {
        $employees = $this->employeeCount();

        $acknowledged = $sop->acknowledgments()
            ->where('sop_version', $sop->version)
            ->whereIn('user_id', $this->employeeIds())
            ->count();

        $anyVersion = $sop->acknowledgments()
            ->whereIn('user_id', $this->employeeIds())
            ->distinct()
            ->count('user_id');

        return [
            'employees' => $employees,
            'acknowledged' => $acknowledged,
            'outdated' => max(0, $anyVersion - $acknowledged),
            'missing' => max(0, $employees - $anyVersion),
            'percentage' => $this->percentage($acknowledged, $employees),
        ];
    }

    /**
     * Daftar karyawan dengan waktu konfirmasi versi berlaku (current_ack_at)
     * dan konfirmasi versi apa pun (any_ack_at).
     */
    public function employeesQuery(Sop $sop): Builder
    {
        return User::query()
            ->where('role', UserRole::Employee->value)
            ->withMax(
                ['acknowledgments as current_ack_at' => fn ($query) => $query
                    ->where('sop_id', $sop->id)
                    ->where('sop_version', $sop->version)],
                'acknowledged_at'
            )
            ->withMax(
                ['acknowledgments as any_ack_at' => fn ($query) => $query
                    ->where('sop_id', $sop->id)],
                'acknowledged_at'
            );
    }

    /**
     * Saring karyawan menurut status baca: sudah, versi_lama, atau belum.
     * Memakai whereHas karena alias hasil withMax tidak bisa dipakai di WHERE pada PostgreSQL.
     */
    public function filterByStatus(Builder $query, Sop $sop, string $status): Builder
    {
        $current = fn ($q) => $q->where('sop_id', $sop->id)->where('sop_version', $sop->version);
        $any = fn ($q) => $q->where('sop_id', $sop->id);

        return match ($status) {
            'sudah' => $query->whereHas('acknowledgments', $current),
            'versi_lama' => $query->whereDoesntHave('acknowledgments', $current)->whereHas('acknowledgments', $any),
            'belum' => $query->whereDoesntHave('acknowledgments', $any),
            default => $query,
        };
    }

    /**
     * Status baca seorang karyawan, dihitung dari hasil employeesQuery().
     */
    public static function statusOf(User $employee): string
    {
        return match (true) {
            $employee->current_ack_at !== null => 'sudah',
            $employee->any_ack_at !== null => 'versi_lama',
            default => 'belum',
        };
    }

    public function percentage(int $part, int $total): int
    {
        return $total > 0 ? (int) round($part / $total * 100) : 0;
    }

    /**
     * Subquery id karyawan, supaya konfirmasi dari role lain tidak ikut dihitung.
     */
    private function employeeIds(): Builder
    {
        return User::query()->select('id')->where('role', UserRole::Employee->value);
    }
}