<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cuanto sitio hay de verdad
    |--------------------------------------------------------------------------
    |
    | PHP no puede averiguarlo solo. disk_free_space() devuelve el disco de la
    | maquina, que en un hosting compartido es de todos los clientes: contaba
    | cientos de gigas libres cuando la cuenta tenia una cuota minuscula, e
    | invitaba a lanzar una importacion que no cabia.
    |
    | Son dos sitios distintos y los dos hacen falta:
    |
    |   - disco, para el ZIP que se descarga de SUNAT (unos 400 MB)
    |   - base de datos, donde acaban los millones de RUC (unos 3 GB)
    |
    | Se ponen a mano porque solo lo sabe quien contrato el hosting. En blanco,
    | la pantalla no inventa un numero: avisa de que hay que comprobarlo.
    |
    */

    'cuota_disco_gb' => env('PADRON_CUOTA_DISCO_GB'),

    'cuota_bd_gb' => env('PADRON_CUOTA_BD_GB'),

];
