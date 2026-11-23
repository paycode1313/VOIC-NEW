@extends('errors.layout')
@section('code', '401')
@section('mark', '#c7d2fe')
@section('title', 'Masuk dulu, ya')
@section('message', 'Halaman ini hanya untuk akun yang sudah masuk. Latihan tetap bisa dicoba tanpa akun.')
@section('actions')
    <a href="/login" class="btn btn-primary">Masuk</a>
    <a href="/latihan" class="btn">Latihan tanpa akun</a>
@endsection
