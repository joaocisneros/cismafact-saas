<?php

namespace App\Http\Requests\Concerns;

use App\Models\Client;

trait NormalizesDemoPayload
{
    protected function normalizeClientPayload(array $client): array
    {
        return [
            'tipo_documento' => $client['tipo_documento'] ?? $client['tipo_doc'] ?? '0',
            'numero_documento' => $client['numero_documento'] ?? $client['num_doc'] ?? '-',
            'razon_social' => $client['razon_social'] ?? 'CLIENTES VARIOS',
            'nombre_comercial' => $client['nombre_comercial'] ?? null,
            'direccion' => $client['direccion'] ?? '',
            'ubigeo' => $client['ubigeo'] ?? null,
            'distrito' => $client['distrito'] ?? null,
            'provincia' => $client['provincia'] ?? null,
            'departamento' => $client['departamento'] ?? null,
            'telefono' => $client['telefono'] ?? null,
            'email' => $client['email'] ?? null,
        ];
    }

    protected function normalizeDetailsPayload(array $details): array
    {
        return array_map(function ($item) {
            $tipAfeIgv = $item['tip_afe_igv'] ?? '10';
            $porcentajeIgv = (float) ($item['porcentaje_igv'] ?? 18);
            $precioUnitario = (float) ($item['precio_unitario'] ?? $item['mto_precio_unitario'] ?? $item['mto_valor_unitario'] ?? 0);
            $valorUnitario = $item['mto_valor_unitario'] ?? $precioUnitario;

            if (! isset($item['mto_valor_unitario']) && in_array($tipAfeIgv, ['10', '17'], true) && $porcentajeIgv > 0) {
                $valorUnitario = round($precioUnitario / (1 + ($porcentajeIgv / 100)), 10);
            }

            return [
                'codigo' => $item['codigo'] ?? $item['cod_producto'] ?? 'P001',
                'codigo_producto_sunat' => $item['codigo_producto_sunat'] ?? $item['cod_producto_sunat'] ?? null,
                'descripcion' => $item['descripcion'] ?? 'Producto',
                'unidad' => $item['unidad'] ?? 'NIU',
                'cantidad' => $item['cantidad'] ?? 1,
                'mto_valor_unitario' => $valorUnitario,
                'precio_unitario_incluye_igv' => ! isset($item['mto_valor_unitario']) ? $precioUnitario : null,
                'mto_valor_gratuito' => $item['mto_valor_gratuito'] ?? null,
                'porcentaje_igv' => $porcentajeIgv,
                'porcentaje_ivap' => $item['porcentaje_ivap'] ?? null,
                'tip_afe_igv' => $tipAfeIgv,
                'isc' => $item['isc'] ?? null,
                'icbper' => $item['icbper'] ?? null,
                'factor_icbper' => $item['factor_icbper'] ?? null,
            ];
        }, $details);
    }

    protected function normalizeAffectedDocumentNumber(): ?string
    {
        $serie = $this->input('doc_afectado_serie');
        $correlativo = $this->input('doc_afectado_correlativo');

        if (! $serie || ! $correlativo) {
            return $this->input('num_doc_afectado');
        }

        if (str_contains((string) $correlativo, '-')) {
            return strtoupper((string) $correlativo);
        }

        return strtoupper($serie) . '-' . str_pad((string) $correlativo, 6, '0', STR_PAD_LEFT);
    }

    protected function findOrCreateDemoClient(array $client): int
    {
        $client = $this->normalizeClientPayload($client);
        $companyId = $this->input('company_id');

        $model = Client::firstOrNew([
            'tipo_documento' => $client['tipo_documento'],
            'numero_documento' => $client['numero_documento'],
        ]);

        if (! $model->exists) {
            $model->fill([
                'company_id' => $companyId,
                'razon_social' => $client['razon_social'],
                'nombre_comercial' => $client['nombre_comercial'],
                'direccion' => $client['direccion'],
                'ubigeo' => $client['ubigeo'],
                'distrito' => $client['distrito'],
                'provincia' => $client['provincia'],
                'departamento' => $client['departamento'],
                'telefono' => $client['telefono'],
                'email' => $client['email'],
                'activo' => true,
            ]);
        } elseif (! $model->company_id && $companyId) {
            $model->company_id = $companyId;
        }

        $model->save();

        return $model->id;
    }
}
