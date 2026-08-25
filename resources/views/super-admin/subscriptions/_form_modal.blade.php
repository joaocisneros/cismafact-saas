<form method="POST" action="{{ $subscription ? route('super-admin.subscriptions.update', $subscription) : route('super-admin.subscriptions.store') }}"
      data-success-message="{{ $subscription ? 'Suscripción actualizada correctamente.' : 'Suscripción creada correctamente.' }}"
      class="grid gap-4 p-5 md:grid-cols-2">
    @csrf
    @if($subscription)
        @method('PUT')
    @endif

    <label class="text-sm font-medium text-gray-700">Empresa
        @if($subscription)
            <input type="hidden" name="company_id" value="{{ $subscription->company_id }}">
            <input value="{{ $subscription->company->ruc }} - {{ $subscription->company->razon_social }}" disabled
                   class="mt-1 w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-600">
        @else
            <select name="company_id" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->ruc }} - {{ $company->razon_social }}</option>
                @endforeach
            </select>
        @endif
    </label>

    <label class="text-sm font-medium text-gray-700">Plan
        <select name="plan_id" required
                onchange="window.syncSubscriptionPlan(this)"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" data-price="{{ $plan->monthly_price }}"
                        @selected((string) ($subscription->plan_id ?? '') === (string) $plan->id)>
                    {{ $plan->name }} - S/ {{ number_format((float) $plan->monthly_price, 2) }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700">Estado
        {{-- "Vencida" no se ofrece: no es una decision, es una consecuencia de
             la fecha. La pone el sistema al pasar el vencimiento y se quita
             sola al poner una fecha futura. Ofrecerla llevaba a dejar la
             suscripcion en "Vencida" con fecha valida, y la empresa seguia
             bloqueada sin motivo aparente. --}}
        <select name="status" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            @foreach(['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada'] as $value => $label)
                <option value="{{ $value }}" @selected(($subscription->status ?? 'active') === $value || (($subscription->status ?? '') === 'expired' && $value === 'active'))>{{ $label }}</option>
            @endforeach
        </select>
        @if(($subscription->status ?? '') === 'expired')
            <span class="mt-1 block text-xs font-normal text-amber-700">
                Está vencida. Pon una fecha de vencimiento futura y déjala en «Activa» para devolverle el acceso.
            </span>
        @endif
    </label>

    <label class="text-sm font-medium text-gray-700">Precio mensual
        <input type="number" step="0.01" min="0" name="monthly_price"
               value="{{ $subscription?->plan?->monthly_price ?? $plans->first()?->monthly_price ?? 0 }}"
               readonly
               class="mt-1 w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-600">
    </label>

    <label class="text-sm font-medium text-gray-700">Inicio
        <input type="date" name="starts_at" value="{{ $subscription ? $subscription->starts_at->toDateString() : now()->toDateString() }}" required
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="text-sm font-medium text-gray-700">Vencimiento
        <input type="date" name="ends_at" value="{{ $subscription?->ends_at?->toDateString() }}"
               onchange="window.syncSubscriptionRenewal(this.form)"
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="text-sm font-medium text-gray-700">Próximo cobro
        <input type="date" name="next_billing_at" value="{{ $subscription?->next_billing_at?->toDateString() }}"
               readonly
               class="mt-1 w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-600">
    </label>

    <label class="flex items-center gap-2 self-end pb-2 text-sm text-gray-700">
        <input type="checkbox" name="auto_renew" value="1"
               @checked($subscription->auto_renew ?? false)
               @disabled((float) ($subscription?->plan?->monthly_price ?? $plans->first()?->monthly_price ?? 0) <= 0)
               onchange="window.syncSubscriptionRenewal(this.form)"
               class="rounded border-gray-300 disabled:cursor-not-allowed disabled:opacity-50">
        Renovación automática
    </label>

    <label class="text-sm font-medium text-gray-700 md:col-span-2">Notas
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ $subscription->notes ?? '' }}</textarea>
    </label>

    <p data-renewal-help class="text-xs text-gray-500 md:col-span-2">
        @if((float) ($subscription?->plan?->monthly_price ?? $plans->first()?->monthly_price ?? 0) > 0)
            Activa esta opción para renovar el plan automáticamente al vencer.
        @else
            El plan Free no necesita renovación automática porque no tiene vencimiento.
        @endif
    </p>

    <div class="flex justify-end gap-2 md:col-span-2">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white">Guardar</button>
    </div>
</form>
