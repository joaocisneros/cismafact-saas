<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $plans = Plan::query()
            ->withCount('companies')
            ->when($request->filled('search'), fn ($query) => $query
                ->where('name', 'like', '%' . $request->string('search')->trim() . '%'))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('active', $request->input('status') === 'active'))
            ->orderBy('monthly_price')
            ->simplePaginate(10)
            ->withQueryString();

        $activePlans = Plan::where('active', true)
            ->orderBy('monthly_price')
            ->get(['id', 'name']);

        $companies = Cache::remember('super_admin_company_plan_options', now()->addMinutes(5), fn () => Company::orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'razon_social', 'ruc', 'plan_id']));

        return view('super-admin.plans.index', compact('plans', 'activePlans', 'companies'));
    }

    public function create(): View
    {
        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.plans._form_modal');
        }

        return view('super-admin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validatedPlan($request));

        return redirect()->route('super-admin.plans')
            ->with('success', 'Plan creado correctamente.');
    }

    public function edit(Plan $plan): View
    {
        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.plans._form_modal', compact('plan'));
        }

        return view('super-admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validatedPlan($request, $plan));

        return redirect()->route('super-admin.plans')
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->companies()->exists()) {
            return back()->with('error', 'No se puede eliminar un plan asignado a empresas.');
        }

        $plan->delete();

        return redirect()->route('super-admin.plans')
            ->with('success', 'Plan eliminado correctamente.');
    }

    public function toggle(Plan $plan): RedirectResponse
    {
        $plan->update(['active' => ! $plan->active]);

        return back()->with('success', $plan->active ? 'Plan activado.' : 'Plan desactivado.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::where('active', true)->findOrFail($validated['plan_id']);
        $company = Company::findOrFail($validated['company_id']);

        DB::transaction(function () use ($company, $plan) {
            $subscription = $company->subscription;
            $isPaidPlan = (float) $plan->monthly_price > 0;
            $endsAt = $isPaidPlan
                ? (($subscription?->ends_at && $subscription->ends_at->isFuture())
                    ? $subscription->ends_at
                    : now()->addMonthNoOverflow()->toDateString())
                : null;

            $company->update(['plan_id' => $plan->id]);

            $subscription = Subscription::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'plan_id' => $plan->id,
                    'status' => $company->activo ? 'active' : 'suspended',
                    'starts_at' => $subscription?->starts_at ?? now()->toDateString(),
                    'ends_at' => $endsAt,
                    'next_billing_at' => $subscription?->auto_renew ? $endsAt : null,
                    'monthly_price' => $plan->monthly_price,
                    'auto_renew' => $isPaidPlan && ($subscription?->auto_renew ?? false),
                ]
            );

            app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
        });
        Cache::forget('super_admin_company_plan_options');
        Cache::forget('super_admin_dashboard_alerts');

        return back()->with('success', "Plan {$plan->name} asignado a {$company->razon_social}.");
    }

    private function validatedPlan(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:plans,name,' . $plan?->id],
            'monthly_document_limit' => ['required', 'integer', 'min:0'],
            'user_limit' => ['required', 'integer', 'min:0'],
            'api_request_limit' => ['required', 'integer', 'min:0'],
            'support_included' => ['required', 'string', 'max:120'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = Str::slug($validated['name']);

        $duplicateCode = Plan::where('code', $validated['code'])
            ->when($plan, fn ($query) => $query->where('id', '!=', $plan->id))
            ->exists();

        if ($duplicateCode) {
            throw ValidationException::withMessages([
                'name' => 'Este nombre genera un código de plan que ya está en uso.',
            ]);
        }

        $validated['active'] = $request->boolean('active', true);

        return $validated;
    }
}
