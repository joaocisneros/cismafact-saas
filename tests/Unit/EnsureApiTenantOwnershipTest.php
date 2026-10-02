<?php

use App\Http\Middleware\EnsureApiTenantOwnership;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

function tenantRequest(Company $authenticated, Company $requested): Request
{
    $request = Request::create('/api/v1/companies/'.$requested->id, 'GET');
    $request->attributes->set('api_company', $authenticated);

    $route = new Route('GET', '/api/v1/companies/{company}', fn () => null);
    $route->bind($request);
    $route->setParameter('company', $requested);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

test('permite acceder a recursos de la empresa autenticada', function () {
    $company = new Company();
    $company->id = 10;

    $response = (new EnsureApiTenantOwnership())->handle(
        tenantRequest($company, $company),
        fn () => new Response('ok')
    );

    expect($response->getStatusCode())->toBe(200);
});

test('oculta recursos pertenecientes a otra empresa', function () {
    $authenticated = new Company();
    $authenticated->id = 10;
    $foreign = new Company();
    $foreign->id = 20;

    $response = (new EnsureApiTenantOwnership())->handle(
        tenantRequest($authenticated, $foreign),
        fn () => new Response('should not run')
    );

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true)['message'])->toBe('No se encontró el recurso.');
});
