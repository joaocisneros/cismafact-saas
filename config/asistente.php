<?php

return [

    /*
     * El asistente de la web, contra OpenRouter.
     *
     * Se apaga solo si no hay clave: el chat no aparece en la pagina en vez de
     * salir y fallar al primer mensaje.
     */
    'clave' => env('OPENROUTER_API_KEY'),

    'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1/chat/completions'),

    /*
     * Los modelos, en el orden en que se intentan.
     *
     * Los gratuitos de OpenRouter se agotan por cuota, asi que cuando uno
     * responde 429 —o cualquier error del servidor— se pasa al siguiente en vez
     * de dejar al visitante con un «no se pudo». Si caen todos, el chat lo dice
     * y ofrece el WhatsApp: no se cobra nada por detras sin avisar.
     *
     * Se cambian desde el .env sin tocar codigo, separados por comas: los
     * gratuitos de OpenRouter aparecen y desaparecen cada pocos meses.
     */
    'modelos' => array_values(array_filter(array_map('trim', explode(',', env('OPENROUTER_MODELOS',
        // Probados uno a uno contra OpenRouter, en este orden.
        //
        // minimax es el que responde: en español, al grano y sin irse por las
        // ramas. Los gemma van detras porque su cuota se agota a ratos y
        // entonces contestan 429.
        //
        // «openrouter/free» —el que elige modelo gratis por ti— va el ultimo y
        // no el primero, aunque parezca lo natural: de seis pruebas solo una
        // devolvio texto. Enruta a modelos diminutos que contestan 200 con el
        // contenido vacio. Sirve de ultimo recurso, no de puerta de entrada.
        //
        // Descartados: nemotron, que mete su razonamiento en ingles dentro de
        // la respuesta, e inkling, que da 403 con esta clave. Y «openrouter/auto»
        // ni se pone: tira de modelos de pago y gastaria saldo sin avisar.
        'minimax/minimax-m3:free,'
        . 'google/gemma-4-31b-it:free,'
        . 'google/gemma-4-26b-a4b-it:free,'
        . 'openrouter/free'
    ))))),

    /*
     * Cuanto se espera a un modelo antes de pasar al siguiente.
     *
     * Corto a proposito: es un chat, y quien pregunta esta mirando la pantalla.
     * Mas vale cambiar de modelo que tenerlo esperando quince segundos.
     */
    'timeout' => (int) env('OPENROUTER_TIMEOUT', 12),

    /*
     * Lo que se le deja escribir a un visitante.
     *
     * La pagina esta abierta a internet: sin tope, uno solo puede tener el
     * chat trabajando toda la noche y agotar la cuota gratuita del dia para
     * todos los demas.
     */
    'limites' => [
        'mensajes_por_visitante' => (int) env('ASISTENTE_MENSAJES_VISITANTE', 12),
        'por_minuto' => (int) env('ASISTENTE_POR_MINUTO', 6),
        'largo_maximo' => (int) env('ASISTENTE_LARGO_MAXIMO', 500),
        // Cuantos mensajes de ida y vuelta se le mandan al modelo como
        // contexto. Mas historia es mas tokens y mas lento, y para resolver
        // dudas de la web con los ultimos basta.
        'historial' => (int) env('ASISTENTE_HISTORIAL', 6),
    ],

    /*
     * Adonde se manda a quien necesita una persona.
     *
     * El asistente responde de lo que hay en la web; en cuanto piden algo de su
     * cuenta, un precio a medida o soporte de verdad, pasa por aqui.
     */
    'whatsapp' => env('ASISTENTE_WHATSAPP', '51921676408'),

];
