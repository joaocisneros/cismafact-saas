<?php

return [

    /*
     * A dónde se pregunta cuando el padrón local no tiene el número.
     *
     * Viene puesto: antes había que escribirlo a mano en Padrón SUNAT y, en
     * una instalación nueva, hasta que alguien lo hacía las consultas de RUC y
     * DNI no respondían —el servicio anotaba «no hay proveedor configurado» y
     * devolvía vacío—. Es la misma dirección para todos, así que no tiene
     * sentido pedirla.
     *
     * Se puede cambiar sin tocar el código: lo que se guarde en Padrón SUNAT
     * manda sobre esto, y CONSULTAS_URL en el .env también.
     *
     * {tipo} es «ruc» o «dni»; {numero}, el número que se consulta.
     */
    'url' => env('CONSULTAS_URL', 'https://api.apis.net.pe/v1/{tipo}?numero={numero}'),

    /*
     * Solo si el proveedor lo pide; va como Bearer. Vacio funciona: el
     * proveedor por defecto responde sin token.
     *
     * Tampoco se pide en pantalla. Es una credencial: escrita en el .env no
     * viaja al navegador ni queda en la base en claro, y se pone una vez.
     */
    'token' => env('CONSULTAS_TOKEN'),

];
