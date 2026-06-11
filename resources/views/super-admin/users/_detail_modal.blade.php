<div class="space-y-5 p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h4>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>
        </div>
        <div class="flex gap-2">
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.users.activity', $user) }}', 'Actividad del usuario')"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Actividad</button>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.users.edit', $user) }}', 'Editar usuario')"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white">Editar</button>
        </div>
    </div>

    <dl class="grid gap-3 rounded-md bg-gray-50 p-4 text-sm md:grid-cols-2">
        <div><dt class="text-gray-500">Empresa</dt><dd class="font-medium text-gray-900">{{ $user->company->razon_social ?? 'N/A' }}</dd></div>
        <div><dt class="text-gray-500">Rol</dt><dd class="font-medium text-gray-900">{{ $user->role->display_name ?? $user->role->name ?? 'Sin rol' }}</dd></div>
        <div><dt class="text-gray-500">Estado</dt><dd class="font-medium {{ $user->active ? 'text-green-700' : 'text-red-700' }}">{{ $user->active ? 'Activo' : 'Inactivo' }}</dd></div>
        <div><dt class="text-gray-500">Último acceso</dt><dd class="font-medium text-gray-900">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd></div>
        <div><dt class="text-gray-500">Intentos fallidos</dt><dd class="font-medium text-gray-900">{{ $user->failed_login_attempts }}</dd></div>
        <div><dt class="text-gray-500">Registro</dt><dd class="font-medium text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</dd></div>
    </dl>

    <div>
        <h5 class="mb-2 text-sm font-semibold text-gray-800">Últimos accesos</h5>
        <div class="divide-y divide-gray-100 border-y border-gray-200">
            @forelse($loginHistory->take(5) as $log)
                <div class="flex justify-between py-2 text-sm">
                    <span>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</span>
                    <span class="{{ $log->success ? 'text-green-700' : 'text-red-700' }}">{{ $log->success ? 'Exitoso' : 'Fallido' }}</span>
                </div>
            @empty
                <p class="py-3 text-sm text-gray-500">Sin historial de accesos.</p>
            @endforelse
        </div>
    </div>
</div>
