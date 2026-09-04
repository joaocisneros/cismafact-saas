<?php

namespace App\Support;

/**
 * Los catalogos del Anexo VIII de SUNAT, en un solo sitio.
 *
 * Estaban repetidos a mano en cada formulario y se habian separado entre si:
 * la configuracion de empresa ofrecia euros que la emision rechazaba, se podia
 * dar de alta un cliente con pasaporte y luego no facturarle, y la API de
 * empresa admitia cuatro afectaciones de IGV donde la v1 admitia las
 * dieciocho. Copiar una lista es facil; acordarse de copiarla otra vez cuando
 * SUNAT la cambia, no.
 *
 * Lo que depende de una fecha no vive aqui sino en {@see NormativaSunat}: esto
 * es lo que SUNAT admite hoy, sin condiciones.
 */
class CatalogoSunat
{
    /**
     * Catalogo 02 - monedas.
     *
     * SUNAT remite a la ISO 4217 entera, que son ciento y pico monedas. Aqui
     * estan las que se usan de verdad en el comercio peruano; ampliar la lista
     * es añadir una linea, y es mejor eso que aceptar cualquier codigo de tres
     * letras y que el rechazo llegue desde SUNAT media hora despues.
     */
    public const MONEDAS = [
        'PEN', 'USD', 'EUR', 'GBP', 'CHF', 'JPY',
        'CNY', 'CAD', 'AUD', 'BRL', 'CLP', 'COP', 'MXN', 'ARS',
    ];

    /**
     * Catalogo 06 - tipo de documento de identidad.
     *
     * Los cuatro ultimos son para quien no vive en el pais: cedula
     * diplomatica, documento del pais de residencia y los numeros tributarios
     * extranjeros. Sin ellos no se puede facturar una exportacion.
     */
    public const DOCUMENTOS_IDENTIDAD = ['0', '1', '4', '6', '7', 'A', 'B', 'C', 'D'];

    /** Los documentos de identidad con su nombre, para los desplegables. */
    public const DOCUMENTOS_IDENTIDAD_NOMBRE = [
        '1' => 'DNI',
        '6' => 'RUC',
        '4' => 'Carnet de extranjería',
        '7' => 'Pasaporte',
        '0' => 'Doc. trib. no domiciliado sin RUC',
        'A' => 'Cédula diplomática de identidad',
        'B' => 'Documento del país de residencia',
        'C' => 'Número tributario extranjero (persona natural)',
        'D' => 'Número tributario extranjero (persona jurídica)',
    ];

    /**
     * Catalogo 07 - tipo de afectacion del IGV.
     *
     * Los dieciocho. Las decenas son la operacion onerosa de cada familia
     * (10 gravado, 20 exonerado, 30 inafecto, 40 exportacion) y el resto son
     * retiros, bonificaciones y transferencias gratuitas, que tambien se
     * declaran. El 17 es IVAP, el arroz pilado, que lleva su propia tasa.
     */
    public const AFECTACIONES_IGV = [
        '10', '11', '12', '13', '14', '15', '16', '17',
        '20', '21',
        '30', '31', '32', '33', '34', '35', '36',
        '40',
    ];

    /** Catalogo 09 - motivo de la nota de credito. */
    public const MOTIVOS_NOTA_CREDITO = [
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13',
    ];

    /**
     * Las que puede llevar el adquirente de una factura.
     *
     * La factura pide RUC, salvo cuando se exporta: al que no vive aqui se le
     * identifica con su pasaporte o con su numero tributario de fuera, porque
     * RUC no tiene.
     */
    public const DOCUMENTOS_FACTURA = ['6', '0', '4', '7', 'A', 'B', 'C', 'D'];

    /**
     * Las afectaciones que pagan IGV.
     *
     * No es solo la 10: un retiro o una bonificacion gravada tampoco se cobra,
     * pero el IGV se calcula igual sobre su valor referencial y se declara. Se
     * mandaba 0% para todo lo que no fuera la 10, asi que esas lineas habrian
     * salido sin impuesto.
     *
     * La 17 se queda fuera a proposito: el IVAP tiene su propia tasa y su
     * propio campo, y {@see \App\Services\DocumentService} lo trata aparte.
     */
    public const AFECTACIONES_CON_IGV = ['10', '11', '12', '13', '14', '15', '16'];

    /** Las afectaciones que son exportacion, donde el adquirente no lleva RUC. */
    public const AFECTACION_EXPORTACION = '40';

    /**
     * Las monedas con su nombre, para los desplegables.
     *
     * Mismo orden que arriba: primero las dos de siempre, luego las que
     * aparecen en comercio exterior.
     */
    public const MONEDAS_NOMBRE = [
        'PEN' => 'Soles',
        'USD' => 'Dólares',
        'EUR' => 'Euros',
        'GBP' => 'Libras esterlinas',
        'CHF' => 'Francos suizos',
        'JPY' => 'Yenes',
        'CNY' => 'Yuanes',
        'CAD' => 'Dólares canadienses',
        'AUD' => 'Dólares australianos',
        'BRL' => 'Reales',
        'CLP' => 'Pesos chilenos',
        'COP' => 'Pesos colombianos',
        'MXN' => 'Pesos mexicanos',
        'ARS' => 'Pesos argentinos',
    ];

    /**
     * Las afectaciones agrupadas por familia, para los desplegables.
     *
     * Dieciocho opciones seguidas no se leen. Agrupadas se entienden de un
     * vistazo, porque es como estan pensadas: una operacion onerosa por
     * familia y, colgando de ella, las que no se cobran.
     */
    public const AFECTACIONES_POR_FAMILIA = [
        'Gravado' => [
            '10' => 'Operación onerosa',
            '11' => 'Retiro por premio',
            '12' => 'Retiro por donación',
            '13' => 'Retiro',
            '14' => 'Retiro por publicidad',
            '15' => 'Bonificaciones',
            '16' => 'Retiro por entrega a trabajadores',
            '17' => 'IVAP (arroz pilado)',
        ],
        'Exonerado' => [
            '20' => 'Operación onerosa',
            '21' => 'Transferencia gratuita',
        ],
        'Inafecto' => [
            '30' => 'Operación onerosa',
            '31' => 'Retiro por bonificación',
            '32' => 'Retiro',
            '33' => 'Retiro por muestras médicas',
            '34' => 'Retiro por convenio colectivo',
            '35' => 'Retiro por premio',
            '36' => 'Retiro por publicidad',
        ],
        'Exportación' => [
            '40' => 'Exportación',
        ],
    ];

    /** Si esa afectacion paga IGV. */
    public static function llevaIgv(?string $afectacion): bool
    {
        return in_array((string) $afectacion, self::AFECTACIONES_CON_IGV, true);
    }

    /** Para meter en una regla «in:» de validacion. */
    public static function paraRegla(array $codigos): string
    {
        return 'in:' . implode(',', $codigos);
    }
}
