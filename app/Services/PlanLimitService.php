<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class PlanLimitService
{
    public function apiRequestsUsedThisMonth(int $companyId): int
    {
        return DB::table('api_usage')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function documentsUsedThisMonth(int $companyId): int
    {
        return collect([
            'invoices',
            'boletas',
            'credit_notes',
            'debit_notes',
            'dispatch_guides',
        ])->sum(fn (string $table) => DB::table($table)
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count());
    }

    public function usersUsed(Company $company): int
    {
        return $company->users()->count();
    }

    public function limitReached(int $limit, int $used): bool
    {
        return $limit > 0 && $used >= $limit;
    }
}
