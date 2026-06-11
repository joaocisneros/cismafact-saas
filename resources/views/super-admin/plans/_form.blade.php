@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Nombre del plan</label>
        <input id="name" name="name" type="text" value="{{ old('name', $plan->name ?? '') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="monthly_price" class="block text-sm font-medium text-gray-700">Precio mensual</label>
        <input id="monthly_price" name="monthly_price" type="number" step="0.01" min="0" value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('monthly_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="monthly_document_limit" class="block text-sm font-medium text-gray-700">Límite documentos mensuales</label>
        <input id="monthly_document_limit" name="monthly_document_limit" type="number" min="0" value="{{ old('monthly_document_limit', $plan->monthly_document_limit ?? 0) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('monthly_document_limit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="user_limit" class="block text-sm font-medium text-gray-700">Límite usuarios</label>
        <input id="user_limit" name="user_limit" type="number" min="0" value="{{ old('user_limit', $plan->user_limit ?? 0) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('user_limit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="api_request_limit" class="block text-sm font-medium text-gray-700">Límite API Requests</label>
        <input id="api_request_limit" name="api_request_limit" type="number" min="0" value="{{ old('api_request_limit', $plan->api_request_limit ?? 0) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('api_request_limit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="support_included" class="block text-sm font-medium text-gray-700">Soporte incluido</label>
        <input id="support_included" name="support_included" type="text" value="{{ old('support_included', $plan->support_included ?? 'Basico') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
        @error('support_included') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<p class="mt-3 text-xs text-gray-500">Usa 0 para indicar que un recurso no tiene límite.</p>

<label class="mt-5 flex items-center gap-2 text-sm text-gray-700">
    <input type="checkbox" name="active" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('active', $plan->active ?? true))>
    Plan activo
</label>
