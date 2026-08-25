@extends('layouts.app')

@section('title', 'Soporte')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Soporte</h1>
            <p class="text-gray-500 mt-1">Envía tu consulta o reporta un problema</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('empresa.support.store') }}" method="POST">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asunto *</label>
                    <input type="text" name="subject" value="{{ old('subject', request('subject')) }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="Describe brevemente tu problema o consulta">
                    @error('subject')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo *</label>
                    <select name="motivo" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        @foreach(\App\Models\Ticket::MOTIVOS as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('motivo', request('motivo', 'soporte')) === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Nos ayuda a dirigir tu mensaje a quien corresponde.</p>
                    @error('motivo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje *</label>
                    <textarea name="message" rows="6" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                              placeholder="Describe tu problema con detalle. Incluye pasos para reproducir si es posible.">{{ old('message', request('message')) }}</textarea>
                    @error('message')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('empresa.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Cancelar</a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">Enviar Ticket</button>
            </div>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-blue-800 mb-2">Consejos para un buen reporte:</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Describe el problema con detalle</li>
            <li>• Incluye el mensaje de error exacto si lo hay</li>
            <li>• Menciona los pasos que realizaste antes del error</li>
            <li>• Indica si es urgente (afecta operaciones diarias)</li>
        </ul>
    </div>
</div>
@endsection
