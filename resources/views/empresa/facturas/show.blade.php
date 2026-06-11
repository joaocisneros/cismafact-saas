@extends('layouts.app')

@section('title', 'Factura ' . $factura->numero_completo)

@section('content')
@include('empresa.facturas._detail', ['modal' => false])
@endsection
