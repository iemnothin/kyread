@extends('errors.layout')

@section('code', '403')
@section('icon', 'bi-shield-lock')
@section('heading', 'Akses ditolak')
@section('message', $exception->getMessage() ?: 'Kamu tidak punya izin untuk membuka halaman ini.')
