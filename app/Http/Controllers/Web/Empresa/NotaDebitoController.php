<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Requests\Empresa\StoreNotaDebitoRequest;
use App\Models\DebitNote;

class NotaDebitoController extends BaseNotaController
{
    protected function tipoDocumento(): string { return '08'; }
    protected function documentType(): string { return 'debit_note'; }
    protected function modelClass(): string { return DebitNote::class; }
    protected function viewPrefix(): string { return 'empresa.notas-debito'; }
    protected function routePrefix(): string { return 'notas-debito'; }
    protected function motivos(): array { return StoreNotaDebitoRequest::motivos(); }

    protected function createDocument(array $data)
    {
        return $this->documentService->createDebitNote($data);
    }

    public function store(StoreNotaDebitoRequest $request)
    {
        return $this->handleStore($request->toServiceData());
    }
}
