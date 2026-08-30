<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * El hecho de haber consultado, que es lo que se cobra.
 *
 * Distinto de la ficha guardada: dos clientes preguntando por el mismo RUC
 * gastan dos consultas aunque el dato salga de la misma fila.
 */
class ConsultaConsumo extends Model
{
    protected $table = 'consultas_consumo';

    public $timestamps = false;

    protected $fillable = ['company_id', 'api_id', 'origen', 'tipo', 'numero', 'fuente', 'created_at'];
}
