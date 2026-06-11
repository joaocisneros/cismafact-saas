@extends('layouts.app')
@section('title', 'Nueva Nota de Débito')
@section('content')
@include('empresa.notas._create', ['titulo' => 'Nota de Débito'])
@endsection
