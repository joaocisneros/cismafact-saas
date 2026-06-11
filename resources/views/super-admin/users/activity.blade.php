@extends('layouts.app')

@section('title', 'Actividad - ' . $user->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Actividad de {{ $user->name }}</h1>
            <p class="text-gray-500 mt-1">{{ $user->email }}</p>
        </div>
        <a href="{{ route('super-admin.users.show', $user) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Historial de Accesos</h2>
        @if($loginHistory->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-3 font-medium">Fecha/Hora</th>
                            <th class="pb-3 font-medium">IP</th>
                            <th class="pb-3 font-medium">Navegador</th>
                            <th class="pb-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loginHistory as $log)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="py-3 text-gray-500">{{ $log->ip_address ?? 'N/A' }}</td>
                            <td class="py-3 text-gray-500 text-xs max-w-xs truncate">{{ $log->user_agent ?? 'N/A' }}</td>
                            <td class="py-3">
                                <span class="px-2 py-1 {{ $log->success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs">
                                    {{ $log->success ? 'Exitoso' : 'Fallido' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">No hay historial de accesos registrado.</p>
        @endif
    </div>
</div>
@endsection
