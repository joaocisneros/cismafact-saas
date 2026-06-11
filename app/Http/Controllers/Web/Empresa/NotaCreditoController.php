<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Requests\Empresa\StoreNotaCreditoRequest;
use App\Models\CreditNote;

class NotaCreditoController extends BaseNotaController
{
    protected function tipoDocumento(): string { return '07'; }
    protected function documentType(): string { return 'credit_note'; }
    protected function modelClass(): string { return CreditNote::class; }
    protected function viewPrefix(): string { return 'empresa.notas-credito'; }
    protected function routePrefix(): string { return 'notas-credito'; }
    protected function motivos(): array { return StoreNotaCreditoRequest::MOTIVOS; }

    protected function createDocument(array $data)
    {
        return $this->documentService->createCreditNote($data);
    }

    public function store(StoreNotaCreditoRequest $request)
    {
        return $this->handleStore($request->toServiceData());
    }
}
