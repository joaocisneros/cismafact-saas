{{--
    Las monedas que SUNAT admite, en un solo sitio.

    Estaban escritas a mano en cada pantalla y solo traian soles y dolares,
    mientras la configuracion de empresa ya ofrecia euros. Ahora las tres
    pantallas de emision y la configuracion sacan la lista de CatalogoSunat.
--}}
@props(['seleccionada' => 'PEN'])

<select {{ $attributes->merge(['class' => 'mt-1 w-full rounded-md border border-gray-300 px-3 py-2']) }}>
    @foreach(\App\Support\CatalogoSunat::MONEDAS_NOMBRE as $codigo => $nombre)
        <option value="{{ $codigo }}" @selected(old($attributes->get('name'), $seleccionada) === $codigo)>
            {{ $codigo }} ({{ $nombre }})
        </option>
    @endforeach
</select>
