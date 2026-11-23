@extends('errors.layout')
@section('code', '503')
@section('mark', '#bbf7d0')
@section('title', 'VOIC sedang disiapkan')
@section('message', 'Kami sedang memperbarui aplikasi. Biasanya hanya beberapa menit. Coba lagi sebentar lagi.')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Coba lagi</button>
@endsection
