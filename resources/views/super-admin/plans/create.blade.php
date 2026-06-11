@extends('layouts.app')

@section('title', 'Crear plan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5">
            <a href="{{ route('super-admin.plans') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Volver a planes</a>
            <h2 class="mt-2 text-xl font-semibold text-gray-900">Crear plan</h2>
        </div>

        <form method="POST" action="{{ route('super-admin.plans.store') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @include('super-admin.plans._form')

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('super-admin.plans') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar</button>
            </div>
        </form>
    </div>
@endsection
