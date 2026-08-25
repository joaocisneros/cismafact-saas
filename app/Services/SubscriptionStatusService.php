<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionStatusService
{
    public function synchronize(Subscription $subscription): Subscription
    {
        if (! in_array($subscription->status, ['trial', 'active'], true)) {
            $this->synchronizeCompanyAccess($subscription, false);

            return $subscription;
        }

        if ($subscription->ends_at && $subscription->ends_at->isBefore(today())) {
            if ($subscription->auto_renew) {
                $renewedEnd = today()->addMonthNoOverflow();

                $subscription->update([
                    'status' => 'active',
                    'ends_at' => $renewedEnd,
                    'next_billing_at' => $renewedEnd,
                ]);
            } else {
                $subscription->update([
                    'status' => 'expired',
                    'next_billing_at' => null,
                ]);
            }
        }

        $subscription->refresh();
        $this->synchronizeCompanyAccess($subscription, $subscription->allowsAccess());

        return $subscription;
    }

    public function synchronizeDueSubscriptions(): int
    {
        $processed = 0;

        Subscription::query()
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<', today())
            ->with('company:id,activo')
            ->chunkById(100, function ($subscriptions) use (&$processed) {
                foreach ($subscriptions as $subscription) {
                    $this->synchronize($subscription);
                    $processed++;
                }
            });

        return $processed;
    }

    private function synchronizeCompanyAccess(Subscription $subscription, bool $active): void
    {
        $company = $subscription->company;

        if (! $company) {
            return;
        }

        // Una empresa suspendida a mano solo la reactiva el Super Admin desde
        // Suscripciones. Antes esto la volvia a encender en cuanto la
        // suscripcion seguia vigente, asi que el boton "Suspender empresa" se
        // deshacia solo en la siguiente peticion del cliente.
        if ($active && $company->suspendida_manualmente) {
            return;
        }

        if ($company->activo !== $active) {
            DB::table('companies')
                ->where('id', $subscription->company_id)
                ->update(['activo' => $active, 'updated_at' => now()]);
        }
    }
}
