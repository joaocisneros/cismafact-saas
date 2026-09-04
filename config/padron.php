<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cuanto sitio tienes contratado, en GB
    |--------------------------------------------------------------------------
    |
    | PHP no puede averiguarlo solo. disk_free_space() devuelve el disco de la
    | maquina, que en un hosting compartido es de todos los clientes: contaba
    | cientos de gigas libres cuando la cuenta tenia 1 GB, e invitaba a lanzar
    | una importacion que no cabia.
    |
    | Es una sola cifra aunque hagan falta dos sitios —el ZIP se baja al disco
    | y los RUC acaban en la base de datos—: los hostings compartidos cobran
    | una bolsa unica y la base sale de ella. AlwaysData, por ejemplo, da 1 GB
    | para todo en su plan gratuito.
    |
    | El padron pide unos 3.4 GB, y 6.4 si ya hay uno importado: durante el
    | cambio conviven la tabla vieja y la nueva.
    |
    | En blanco, la pantalla no inventa un numero: avisa de que hay que
    | comprobarlo.
    |
    */

    'cuota_gb' => env('PADRON_CUOTA_GB'),

];
