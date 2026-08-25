@extends('layouts.app')
@section('title', isset($usuario) && $usuario ? 'Editar usuario' : 'Nuevo usuario')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">{{ isset($usuario) && $usuario ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
        <a href="{{ route('empresa.usuarios.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white">
        @include('empresa.usuarios._form')
    </div>
</div>
@endsection
