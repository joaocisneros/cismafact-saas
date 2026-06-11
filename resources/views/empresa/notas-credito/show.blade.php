@extends('layouts.app')
@section('title', 'Nota de Crédito ' . $nota->serie . '-' . $nota->correlativo)
@section('content')
@include('empresa.notas._show', ['titulo' => 'Nota de Crédito'])
@endsection
