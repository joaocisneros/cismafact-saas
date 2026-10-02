@include('empresa.documents._preview', [
    'documento' => $boleta,
    'tipoNombre' => 'Boleta',
    'tipoPlural' => 'Boletas',
    'tipoRuta' => 'boleta',
    'rutaIndice' => 'empresa.boletas.index',
    'rutaSunat' => 'empresa.boletas.send-sunat',
    'modal' => $modal ?? false,
])
