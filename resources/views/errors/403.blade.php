@extends('errors.layout')

@section('code', '403')
@section('icon', 'bi-shield-lock')
@section('heading', __('Akses ditolak'))
@section('message', $exception->getMessage() ?: __('Kamu tidak punya izin untuk membuka halaman ini.'))
