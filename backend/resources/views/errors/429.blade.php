@php($wait = (int) ($exception?->getHeaders()['Retry-After'] ?? 0))
@extends('errors.layout')
@section('code', '429')
@section('mark', '#bae6fd')
@section('title', 'Pelan-pelan dulu, ya')
@section('message')
    Terlalu banyak permintaan dalam waktu singkat.
    {{ $wait > 0 ? 'Coba lagi dalam '.($wait >= 60 ? ceil($wait / 60).' menit' : $wait.' detik').'.' : 'Tunggu sebentar, lalu coba lagi.' }}
@endsection
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Coba lagi</button>
    <a href="/" data-kiosk-href="/sambut" class="btn">Ke beranda</a>
@endsection
