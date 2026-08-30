<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;

/**
 * Lo que gastan las empresas de casa.
 *
 * NO es lo mismo que el consumo de «API RUC y DNI»: alli se mira lo que
 * consumen los de fuera, que es lo que se cobra. Aqui se mira lo que gastan
 * las empresas del propio sistema al emitir y al buscar un RUC o un DNI desde
 * el panel, que no se cobra aparte pero si cuesta —cada consulta que sale al
 * proveedor se paga— y sirve para saber que plan se queda corto.
 *
 * Van separados a proposito. Mezclarlos daria un total que no significa nada:
 * ni lo que se factura ni lo que se gasta.
 *
 * Por armar: todavia no tiene contenido, solo el sitio.
 */
class ConsumoInternoController extends Controller
{
    public function index()
    {
        return view('super-admin.consumo-interno.index');
    }
}
