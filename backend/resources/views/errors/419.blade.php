@extends('errors.layout')
@section('code', '419')
@section('mark', '#ddd6fe')
@section('title', 'Halamannya sudah kelamaan terbuka')
@section('message', 'Demi keamanan, formulir yang terbuka terlalu lama perlu dimuat ulang. Kembali ke halaman sebelumnya, muat ulang, lalu coba lagi.')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="history.length > 1 ? history.back() : location.assign('/')">Kembali &amp; coba lagi</button>
    <a href="/" data-kiosk-href="/sambut" class="btn">Ke beranda</a>
@endsection
