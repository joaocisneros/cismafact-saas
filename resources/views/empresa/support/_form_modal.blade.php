<form method="POST" action="{{ route('empresa.support.store') }}" class="p-5"
      data-success-message="Ticket enviado. Te responderemos pronto.">
    @csrf

    <div class="grid gap-4 md:grid-cols-2">
        <label class="text-sm font-medium text-gray-700 md:col-span-2">Asunto
            <input name="subject" value="{{ old('subject', request('subject')) }}" required maxlength="255"
                   placeholder="Resume tu consulta en una línea"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="text-sm font-medium text-gray-700 md:col-span-2">Motivo
            <select name="motivo" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach(\App\Models\Ticket::MOTIVOS as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('motivo', request('motivo', 'soporte')) === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </label>


        <label class="text-sm font-medium text-gray-700 md:col-span-2">Mensaje
            <textarea name="message" required rows="6"
                      placeholder="Cuéntanos qué ocurre. Si es un error al emitir, indica el número del comprobante."
                      class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('message', request('message')) }}</textarea>
        </label>
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Enviar ticket
        </button>
    </div>
</form>
