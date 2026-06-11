<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Payment::ESTADOS))],
            'method' => ['nullable', Rule::in(array_keys(Payment::METODOS))],
        ]);

        $query = Payment::query()
            ->with([
                'company:id,ruc,razon_social',
                'plan:id,name',
            ]);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('company', function ($q) use ($search) {
                $q->where('ruc', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        $payments = $query
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->simplePaginate(20)
            ->withQueryString();

        // Tarjetas de resumen.
        $stats = [
            'total_confirmado' => Payment::where('status', 'confirmed')->sum('amount'),
            'mes_actual' => Payment::where('status', 'confirmed')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
            'pendientes' => Payment::where('status', 'pending')->count(),
            'total_registros' => Payment::count(),
        ];

        return view('super-admin.payments.index', compact('payments', 'stats'));
    }

    public function create(): View
    {
        $payment = null;
        $companies = Company::orderBy('razon_social')->limit(300)->get(['id', 'ruc', 'razon_social']);
        $plans = Plan::where('active', true)->orderBy('monthly_price')->get(['id', 'name', 'monthly_price']);

        return view('super-admin.payments._form_modal', compact('payment', 'companies', 'plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayment($request);

        DB::transaction(function () use ($validated) {
            $subscription = Subscription::where('company_id', $validated['company_id'])->first();

            $payment = Payment::create([
                'company_id' => $validated['company_id'],
                'subscription_id' => $subscription?->id,
                'plan_id' => $validated['plan_id'] ?? $subscription?->plan_id,
                'amount' => $validated['amount'],
                'currency' => 'PEN',
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'paid_at' => $validated['paid_at'],
                'months' => $validated['months'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'registered_by' => Auth::id(),
            ]);

            // Solo un pago confirmado extiende la suscripcion.
            if ($payment->status === 'confirmed') {
                $this->aplicarPagoASuscripcion($payment, $subscription);
            }
        });

        return redirect()
            ->route('super-admin.payments.index')
            ->with('success', 'Pago registrado correctamente.');
    }

    public function confirm(Payment $payment): RedirectResponse
    {
        if ($payment->status === 'confirmed') {
            return back()->with('success', 'El pago ya estaba confirmado.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'confirmed']);
            $subscription = Subscription::where('company_id', $payment->company_id)->first();
            $this->aplicarPagoASuscripcion($payment, $subscription);
        });

        return back()->with('success', 'Pago confirmado y suscripción activada.');
    }

    public function refund(Payment $payment): RedirectResponse
    {
        $payment->update(['status' => 'refunded']);

        return back()->with('success', 'Pago marcado como reembolsado.');
    }

    /**
     * Extiende la suscripcion de la empresa por los meses pagados.
     */
    private function aplicarPagoASuscripcion(Payment $payment, ?Subscription $subscription): void
    {
        if (! $subscription) {
            return;
        }

        $baseDate = $subscription->ends_at && $subscription->ends_at->isFuture()
            ? $subscription->ends_at
            : now();

        $newEnd = $baseDate->copy()->addMonthsNoOverflow((int) $payment->months);

        $subscription->update([
            'status' => 'active',
            'starts_at' => $subscription->starts_at->isFuture() ? now()->toDateString() : $subscription->starts_at,
            'ends_at' => $newEnd,
            'next_billing_at' => $subscription->auto_renew ? $newEnd : null,
        ]);

        $payment->update(['subscription_id' => $subscription->id]);

        app(SubscriptionStatusService::class)->synchronize($subscription->load('company'));
    }

    private function validatePayment(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'method' => ['required', Rule::in(array_keys(Payment::METODOS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'status' => ['required', Rule::in(array_keys(Payment::ESTADOS))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
