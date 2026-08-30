@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
@php
    // Las pestañas van por la direccion y no por JavaScript: asi se puede
    // enlazar una en concreto, y al guardar un formulario back() vuelve a la
    // misma en vez de tirar al usuario a la primera.
    $pestanas = [
        'planes' => 'Planes',
        'apis' => 'Mis APIs',
        'consumo' => 'Consumo',
        'keys' => 'API Keys',
        'historial' => 'Historial',
        'webhooks' => 'Webhooks',
        'docs' => 'Documentación',
    ];

    $actual = array_key_exists(request('tab'), $pestanas) ? request('tab') : 'planes';
@endphp

<div class="space-y-5">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <nav class="overflow-x-auto border-b border-gray-200">
        <div class="flex gap-1 whitespace-nowrap">
            @foreach($pestanas as $clave => $nombre)
                <a href="{{ route('super-admin.consultas', ['tab' => $clave]) }}"
                   class="border-b-2 px-4 py-3 text-sm transition
                          {{ $actual === $clave
                              ? 'border-blue-600 font-semibold text-blue-600'
                              : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900' }}">
                    {{ $nombre }}
                </a>
            @endforeach
        </div>
    </nav>

    @includeIf('super-admin.consultas.tabs.' . $actual)

</div>
@endsection
