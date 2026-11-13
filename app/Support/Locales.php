<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

/** Daftar bahasa antarmuka dan penerapannya (lihat config/locales.php). */
class Locales
{
    public const string COOKIE = 'locale';

    /** Teks yang dipakai t('...') di resources/js (dikirim ke browser lewat window.KY_I18N). */
    public const array JS_KEYS = [
        'Voucher tidak bisa dipakai.',
        'Gagal memeriksa voucher. Coba lagi.',
        'Kyra sedang mengetik…',
        'Kyra gagal menjawab.',
        'Hapus dari wishlist',
        'Simpan ke wishlist',
        'Tersimpan',
        'Wishlist',
        'Stok tinggal :stock.',
        'Penulis',
        'Kategori',
        'Ukuran foto maksimal 5 MB.',
        'Mengenali buku dari foto…',
        'Gagal memproses foto.',
        'Terdeteksi',
        'Judul tidak terbaca',
        'Belum ada buku yang cocok di katalog. Coba foto lain atau cari dengan kata kunci di atas.',
        'Penjualan',
        'Rp :amount rb',
        'Belum ada penjualan',
    ];

    /** @return array<string, array{native: string, html: string, carbon: string, dir: string}> */
    public static function all(): array
    {
        return config('locales');
    }

    /**
     * Tandai teks sumber yang diterjemahkan belakangan (mis. disimpan di database lalu
     * dirender dengan __() saat ditampilkan). Mengembalikan teks apa adanya.
     */
    public static function key(string $text): string
    {
        return $text;
    }

    public static function supports(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, static::all());
    }

    /** Terapkan bahasa ke aplikasi (teks, validasi) dan Carbon (tanggal). */
    public static function apply(string $locale): void
    {
        $locale = static::supports($locale) ? $locale : config('app.default_locale');

        App::setLocale($locale);
        Carbon::setLocale(static::all()[$locale]['carbon']);
    }

    public static function current(): array
    {
        return static::all()[App::getLocale()] ?? static::all()[config('app.default_locale')];
    }

    /** @return array<string, string> kamus kecil untuk JavaScript halaman ini */
    public static function jsStrings(): array
    {
        return collect(static::JS_KEYS)->mapWithKeys(fn (string $key) => [$key => __($key)])->all();
    }

    public static function dir(): string
    {
        return static::current()['dir'];
    }

    public static function isRtl(): bool
    {
        return static::dir() === 'rtl';
    }

    public static function htmlLang(): string
    {
        return static::current()['html'];
    }
}
