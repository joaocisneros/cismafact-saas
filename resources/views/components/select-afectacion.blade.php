{{--
    Las dieciocho afectaciones al IGV del catálogo 07, agrupadas por familia.

    Las pantallas ofrecían solo cuatro —gravado, exonerado, inafecto y
    exportación—, así que por el panel no se podía emitir una bonificación ni
    un retiro, que SUNAT sí admite y la API v1 ya aceptaba.

    Agrupadas por familia y no en una lista de dieciocho: cada grupo tiene su
    operación onerosa arriba y, colgando, las que no se cobran.

    `excluir` deja fuera las que esta pantalla no sabe emitir bien. El panel
    quita el IVAP (17): lleva su propia tasa, el formulario no la pide y
    saldría sin impuesto. Por la API sigue estando, con `porcentaje_ivap`.
--}}
@props(['excluir' => []])

<select {{ $attributes->merge(['class' => 'rounded border border-gray-300 px-2 py-1.5']) }}>
    @foreach(\App\Support\CatalogoSunat::AFECTACIONES_POR_FAMILIA as $familia => $opciones)
        @php($visibles = array_diff_key($opciones, array_flip($excluir)))
        @if($visibles)
            <optgroup label="{{ $familia }}">
                @foreach($visibles as $codigo => $nombre)
                    <option value="{{ $codigo }}">{{ $nombre }}</option>
                @endforeach
            </optgroup>
        @endif
    @endforeach
</select>
