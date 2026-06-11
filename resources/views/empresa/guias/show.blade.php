@extends('layouts.app')

@section('title', 'Guía ' . $guia->serie . '-' . $guia->correlativo)

@section('content')
@include('empresa.guias._detail', ['modal' => false])
@endsection
