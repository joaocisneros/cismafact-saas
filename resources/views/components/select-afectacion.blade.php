{{--
    Las dieciocho afectaciones al IGV del catálogo 07, agrupadas por familia.

    Las pantallas ofrecían solo cuatro —gravado, exonerado, inafecto y
    exportación—, así que por el panel no se podía emitir una bonificación ni
    un retiro, que SUNAT sí admite y la API v1 ya aceptaba.

    Agrupadas por familia y no en una lista de dieciocho: cada grupo tiene su
    operación onerosa arriba y, colgando, las que no se cobran.
--}}
<select {{ $attributes->merge(['class' => 'rounded border border-gray-300 px-2 py-1.5']) }}>
    @foreach(\App\Support\CatalogoSunat::AFECTACIONES_POR_FAMILIA as $familia => $opciones)
        <optgroup label="{{ $familia }}">
            @foreach($opciones as $codigo => $nombre)
                <option value="{{ $codigo }}">{{ $nombre }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
