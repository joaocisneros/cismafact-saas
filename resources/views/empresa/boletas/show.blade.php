@extends('layouts.app')

@section('title', 'Boleta ' . $boleta->numero_completo)

@section('content')
@include('empresa.boletas._detail', ['modal' => false])
@endsection
