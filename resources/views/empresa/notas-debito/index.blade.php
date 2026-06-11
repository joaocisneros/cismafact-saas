@extends('layouts.app')
@section('title', 'Notas de Débito')
@section('content')
@include('empresa.notas._index', ['titulo' => 'Notas de Débito'])
@endsection
