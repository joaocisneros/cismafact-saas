@extends('layouts.app')

@section('title', 'Consumo interno')

@section('content')
<div class="space-y-5">

    <div>
        <h1 class="text-lg font-semibold text-gray-900">Consumo interno</h1>
        <p class="mt-1 text-sm text-gray-500">
            Lo que gastan las empresas del sistema: comprobantes emitidos y consultas de RUC y DNI
            hechas desde el panel. No es lo que consumen los clientes de la API, que va en
            <a href="{{ route('super-admin.consultas', ['tab' => 'consumo']) }}"
               class="font-medium text-indigo-600 hover:text-indigo-700">API RUC y DNI</a>.
        </p>
    </div>

    <section class="rounded-lg border border-dashed border-gray-300 bg-white px-5 py-14 text-center">
        <p class="text-sm font-medium text-gray-700">Pendiente de armar</p>
        <p class="mt-1 text-xs text-gray-500">El módulo está creado. Dime qué va aquí dentro y lo monto.</p>
    </section>

</div>
@endsection
