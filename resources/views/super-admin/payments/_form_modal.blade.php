<form method="POST" action="{{ route('super-admin.payments.store') }}"
      data-success-message="Pago registrado correctamente."
      class="grid gap-4 p-5 md:grid-cols-2">
    @csrf

    <label class="text-sm font-medium text-gray-700 md:col-span-2">Empresa
        <select name="company_id" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="">Seleccione una empresa…</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->ruc }} - {{ $company->razon_social }}</option>
            @endforeach
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700">Plan (opcional)
        <select name="plan_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="">— Usar plan actual —</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" data-price="{{ $plan->monthly_price }}">
                    {{ $plan->name }} - S/ {{ number_format((float) $plan->monthly_price, 2) }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700">Monto (S/)
        <input type="number" step="0.01" min="0.01" name="amount" required
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="text-sm font-medium text-gray-700">Método de pago
        <select name="method" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            @foreach(\App\Models\Payment::METODOS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700">N° operación / voucher
        <input type="text" name="reference" maxlength="100"
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="text-sm font-medium text-gray-700">Fecha de pago
        <input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
    </label>

    <label class="text-sm font-medium text-gray-700">Meses cubiertos
        <select name="months" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            @foreach([1, 3, 6, 12] as $m)
                <option value="{{ $m }}">{{ $m }} mes{{ $m > 1 ? 'es' : '' }}</option>
            @endforeach
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700">Estado
        <select name="status" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="confirmed">Confirmado (activa la suscripción)</option>
            <option value="pending">Pendiente (no activa todavía)</option>
        </select>
    </label>

    <label class="text-sm font-medium text-gray-700 md:col-span-2">Notas
        <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
    </label>

    <p class="text-xs text-gray-500 md:col-span-2">
        Al confirmar un pago, la suscripción de la empresa se activa y se extiende por los meses indicados.
    </p>

    <div class="flex justify-end gap-2 md:col-span-2">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white">Registrar pago</button>
    </div>
</form>
