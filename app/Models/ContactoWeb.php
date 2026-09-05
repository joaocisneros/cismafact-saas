<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quien dejo sus datos en el chat de la web para que le escriban.
 *
 * No es un cliente todavia ni tiene cuenta: es alguien que estuvo mirando y
 * quiso que le llamaran. Por eso no cuelga de ninguna empresa.
 */
class ContactoWeb extends Model
{
    use HasFactory;

    protected $table = 'contactos_web';

    /*
     * Los cuatro primeros los escribe el visitante; los tres ultimos los pone
     * quien atiende, desde el panel.
     *
     * Faltaban esos tres y update() los descartaba sin decir nada: se pulsaba
     * «marcar como atendido», salia el aviso de que estaba hecho, y el contacto
     * seguia en pendientes.
     */
    protected $fillable = [
        'nombre', 'telefono', 'mensaje', 'interes', 'ip',
        'atendido_en', 'atendido_por', 'nota',
    ];

    protected $casts = [
        'atendido_en' => 'datetime',
    ];

    /** Como se llama la rama del chat de la que salio. */
    public const INTERESES = [
        'facturacion' => 'Facturación electrónica',
        'consultas' => 'Consultas de RUC y DNI',
    ];

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function estaAtendido(): bool
    {
        return $this->atendido_en !== null;
    }

    /** Lo que buscaba, escrito. */
    public function interesTexto(): string
    {
        return self::INTERESES[$this->interes] ?? 'Sin especificar';
    }

    /**
     * El telefono como se marca.
     *
     * Se guarda tal cual lo escriben —con espacios, guiones o el prefijo— y se
     * limpia aqui: para el enlace de WhatsApp hacen falta solo digitos, y con
     * el prefijo de Peru si no lo pusieron.
     */
    public function paraWhatsapp(): string
    {
        $digitos = preg_replace('/\D/', '', (string) $this->telefono);

        // Nueve digitos que empiezan por 9 es un movil peruano sin prefijo.
        if (strlen($digitos) === 9 && str_starts_with($digitos, '9')) {
            return '51' . $digitos;
        }

        return $digitos;
    }

    public function scopePendientes($consulta)
    {
        return $consulta->whereNull('atendido_en');
    }
}
