@extends('layouts.consultas')

@section('title', 'Mis consultas')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Mis consultas</h1>
    <p class="text-[15px] text-gray-600">Todo lo que has preguntado, para que puedas cuadrar tu gasto.</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3.5">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">
            {{ $consultas->total() }} consultas
        </h2>

        <form method="GET" class="flex flex-wrap gap-2">
            <select name="tipo" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                <option value="">Todos los tipos</option>
                <option value="ruc" @selected(request('tipo') === 'ruc')>Solo RUC</option>
                <option value="dni" @selected(request('tipo') === 'dni')>Solo DNI</option>
            </select>
            <select name="entorno" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                <option value="">Todos los entornos</option>
                <option value="produccion" @selected(request('entorno') === 'produccion')>Producción</option>
                <option value="sandbox" @selected(request('entorno') === 'sandbox')>Sandbox</option>
            </select>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
            <button class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-blue-700">Filtrar</button>
            @if(request()->hasAny(['tipo', 'entorno', 'desde']))
                <a href="{{ route('consultas.consultas') }}"
                   class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">Limpiar</a>
            @endif
        </form>
    </div>

    @include('consultas._tabla-consultas', ['filas' => $consultas, 'conCuota' => true])

    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 px-4 py-3 text-xs text-gray-500">
        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ $gastadas }} consultas consumieron cuota este mes</span>
        <span>{{ $consultas->links() }}</span>
    </div>
</div>
@endsection
