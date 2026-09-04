{{--
    Los tipos de documento de identidad del catálogo 06.

    Las pantallas ofrecían cuatro —DNI, RUC, carnet y sin documento— cuando
    SUNAT admite nueve. Sin pasaporte no se puede emitir a un turista, y sin
    los documentos extranjeros no se factura una exportación.
--}}
<select {{ $attributes->merge(['class' => 'mt-1 w-full rounded-md border border-gray-300 px-3 py-2']) }}>
    @foreach(\App\Support\CatalogoSunat::DOCUMENTOS_IDENTIDAD_NOMBRE as $codigo => $nombre)
        <option value="{{ $codigo }}">{{ $nombre }}</option>
    @endforeach
</select>
