@extends('layouts.app')
@section('title', 'Nueva Nota de Crédito')
@section('content')
@include('empresa.notas._create', ['titulo' => 'Nota de Crédito'])
@endsection
