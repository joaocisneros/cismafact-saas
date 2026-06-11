@extends('layouts.app')

@section('title', 'Correlativos')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Correlativos (Series)</h1>
        <p class="text-gray-500 mt-1">Configura las series con las que se numeran tus comprobantes (ej: F001 para facturas).</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Formulario para agregar una serie --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-md font-semibold text-gray-800 mb-4">Agregar serie</h3>
        <form method="POST" action="{{ route('empresa.correlatives.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sucursal</label>
                <select name="branch_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->codigo }} - {{ $branch->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo_documento" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($tipos as $cod => $nombre)
                        <option value="{{ $cod }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serie</label>
                <input type="text" name="serie" maxlength="4" placeholder="F001"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correlativo actual</label>
                <input type="number" name="correlativo_actual" value="0" min="0"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Agregar</button>
        </form>
        <p class="text-xs text-gray-500 mt-2">El "correlativo actual" es el último número emitido. El siguiente documento usará el número +1.</p>
    </div>

    {{-- Series por sucursal --}}
    @forelse($branches as $branch)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700">Sucursal {{ $branch->codigo }} — {{ $branch->nombre }}</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Serie</th>
                        <th class="px-5 py-3">Correlativo actual</th>
                        <th class="px-5 py-3">Próximo número</th>
                        <th class="px-5 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($branch->correlatives as $c)
                        <tr>
                            <td class="px-5 py-3 text-gray-600">{{ $tipos[$c->tipo_documento] ?? $c->tipo_documento }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $c->serie }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $c->correlativo_actual }}</td>
                            <td class="px-5 py-3"><span class="rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $c->numero_completo }}</span></td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('empresa.correlatives.destroy', $c) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar la serie {{ $c->serie }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-4 text-center text-gray-500" colspan="5">Esta sucursal no tiene series configuradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">No hay sucursales registradas.</div>
    @endforelse
</div>
@endsection
