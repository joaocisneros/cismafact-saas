@include('empresa.documents._preview', [
    'documento' => $factura,
    'tipoNombre' => 'Factura',
    'tipoPlural' => 'Facturas',
    'tipoRuta' => 'factura',
    'rutaIndice' => 'empresa.facturas.index',
    'rutaSunat' => 'empresa.facturas.send-sunat',
    'modal' => $modal ?? false,
])
