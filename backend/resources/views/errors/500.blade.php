@extends('errors.layout')
@section('code', '500')
@section('mark', '#fecaca')
@section('title', 'Ada yang salah di sisi kami')
@section('message', 'Bukan salahmu. Coba muat ulang halaman ini. Kalau masih muncul, kembali ke beranda dan mulai lagi.')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Muat ulang</button>
    <a href="/" data-kiosk-href="/sambut" class="btn">Ke beranda</a>
@endsection
@section('foot', 'Di booth? Panggil operator, ya.')
