@extends('layouts.app')
@section('title', 'Nota de Débito ' . $nota->serie . '-' . $nota->correlativo)
@section('content')
@include('empresa.notas._show', ['titulo' => 'Nota de Débito'])
@endsection
