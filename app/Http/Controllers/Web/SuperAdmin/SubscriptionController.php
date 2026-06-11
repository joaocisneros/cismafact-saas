<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['trial', 'active', 'suspended', 'cancelled', 'expired'])],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        app(SubscriptionStatusService::class)->synchronizeDueSubscriptions();

        $query = Subscription::query()
            ->with([
                'company:id,ruc,razon_social,activo',
                'plan:id,name,monthly_price',
            ]);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('company', function ($companyQuery) use ($search) {
                $companyQuery->where('ruc', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        $subscriptions = $query
            ->orderByRaw('ends_at IS NULL, ends_at ASC')
            ->orderByDesc('created_at')
            ->simplePaginate(20)
            ->withQueryString();

        $plans = Plan::where('active', true)->orderBy('monthly_price')->get(['id', 'name', 'monthly_price']);
        return view('super-admin.subscriptions.index', compact('subscriptions', 'plans'));
    }

    public function create(): View
    {
        $subscription = null;
        $plans = Plan::where('active', true)
            ->orderBy('monthly_price')
            ->get(['id', 'name', 'monthly_price']);
        $companies = Company::query()
            ->orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'ruc', 'razon_social']);

        return view('super-admin.subscriptions._form_modal', compact('subscription', 'plans', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSubscription($request);
        $plan = Plan::where('active', true)->findOrFail($validated['plan_id']);

        DB::transaction(function () use ($validated, $plan, $request) {
            $existingSubscription = Subscription::where('company_id', $validated['company_id'])->first();
            $attributes = $this->subscriptionAttributes($validated, $plan, $request, $existingSubscription);

            $subscription = Subscription::updateOrCreate(
                ['company_id' => $validated['company_id']],
                $attributes
            );

            Company::whereKey($validated['company_id'])->update(['plan_id' => $plan->id]);
            app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
        });

        $this->forgetSubscriptionCaches();

        return back()->with('success', 'Suscripción guardada correctamente.');
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $this->validateSubscription($request);

        if ((int) $validated['company_id'] !== $subscription->company_id) {
            abort(422, 'La empresa de la suscripción no coincide.');
        }

        $plan = Plan::where('active', true)->findOrFail($validated['plan_id']);

        DB::transaction(function () use ($validated, $plan, $request, $subscription) {
            $subscription->update(
                $this->subscriptionAttributes($validated, $plan, $request, $subscription)
            );

            Company::whereKey($subscription->company_id)->update(['plan_id' => $plan->id]);
            app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
        });

        $this->forgetSubscriptionCaches();

        return back()->with('success', 'Suscripción actualizada correctamente.');
    }

    public function edit(Subscription $subscription): View
    {
        $subscription->load('company:id,ruc,razon_social');
        $plans = Plan::where('active', true)->orderBy('monthly_price')->get(['id', 'name', 'monthly_price']);
        $companies = collect();

        return view('super-admin.subscriptions._form_modal', compact('subscription', 'plans', 'companies'));
    }

    public function renew(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $baseDate = $subscription->ends_at && $subscription->ends_at->isFuture()
            ? $subscription->ends_at
            : now();

        // Se castea a int: el formulario envia "months" como texto y esta version
        // de Carbon exige int|float (un string lanza TypeError -> error 500).
        $newEnd = $baseDate->copy()->addMonthsNoOverflow((int) $validated['months']);

        $subscription->update([
            'status' => 'active',
            'starts_at' => $subscription->starts_at->isFuture() ? now()->toDateString() : $subscription->starts_at,
            'ends_at' => $newEnd,
            'next_billing_at' => $subscription->auto_renew ? $newEnd : null,
        ]);

        app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
        $this->forgetSubscriptionCaches();

        return back()->with('success', 'Suscripción renovada correctamente.');
    }

    public function changeStatus(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:trial,active,suspended,cancelled,expired'],
        ]);

        DB::transaction(function () use ($subscription, $validated) {
            $subscription->update(['status' => $validated['status']]);
            app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
        });

        $this->forgetSubscriptionCaches();

        return back()->with('success', 'Estado de suscripción actualizado.');
    }

    private function validateSubscription(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:trial,active,suspended,cancelled,expired'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'next_billing_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function subscriptionAttributes(
        array $validated,
        Plan $plan,
        Request $request,
        ?Subscription $existingSubscription = null
    ): array {
        $autoRenew = $request->boolean('auto_renew');
        $endsAt = $validated['ends_at'] ?? null;

        if ((float) $plan->monthly_price <= 0) {
            $endsAt = null;
            $autoRenew = false;
        } elseif (! $endsAt) {
            $endsAt = $existingSubscription?->ends_at?->isFuture()
                ? $existingSubscription->ends_at
                : \Illuminate\Support\Carbon::parse($validated['starts_at'])->addMonthNoOverflow()->toDateString();
        }

        return [
            'plan_id' => $plan->id,
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $endsAt,
            'next_billing_at' => $autoRenew ? $endsAt : null,
            'monthly_price' => $plan->monthly_price,
            'auto_renew' => $autoRenew,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function forgetSubscriptionCaches(): void
    {
        Cache::forget('super_admin_company_plan_options');
        Cache::forget('super_admin_dashboard_minimal_stats');
        Cache::forget('super_admin_dashboard_alerts');
    }
}
