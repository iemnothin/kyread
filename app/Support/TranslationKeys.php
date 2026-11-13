<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Mengumpulkan semua teks sumber (bahasa Indonesia) yang perlu diterjemahkan dari
 * web (PHP/Blade/JS) dan aplikasi Android (Kotlin), lalu membandingkannya dengan
 * lang/{kode}.json. Dipakai `php artisan i18n:check` dan test.
 */
class TranslationKeys
{
    /** Pola pemanggilan per jenis file: [regex, karakter kutip]. */
    private const array PATTERNS = [
        'php' => [
            ["/(?:__|trans_choice|Locales::key)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", "'"],
            ['/(?:__|trans_choice|Locales::key)\\(\\s*"((?:[^"\\\\$]|\\\\.)*)"/', '"'],
        ],
        'js' => [
            ["/\\bt\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", "'"],
        ],
        'kt' => [
            ['/\\b(?:tr|trc)\\(\\s*"((?:[^"\\\\$]|\\\\.)*)"/', '"'],
        ],
    ];

    /** @return array<string, list<string>> kunci => daftar lokasi file */
    public static function collect(): array
    {
        $sources = [
            'php' => [app_path(), resource_path('views'), base_path('routes')],
            'js' => [resource_path('js')],
            'kt' => [base_path('android/app/src/main/java')],
        ];

        $keys = [];
        foreach ($sources as $type => $dirs) {
            foreach ($dirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }
                /** @var SplFileInfo $file */
                foreach (File::allFiles($dir) as $file) {
                    if (! static::matchesType($file->getFilename(), $type)) {
                        continue;
                    }
                    foreach (static::scan($file->getContents(), $type) as $key) {
                        $keys[$key][] = $file->getRelativePathname();
                    }
                }
            }
        }
        ksort($keys);

        return $keys;
    }

    /** @return list<string> */
    public static function scan(string $code, string $type): array
    {
        $found = [];
        foreach (self::PATTERNS[$type] as [$regex, $quote]) {
            preg_match_all($regex, $code, $m);
            foreach ($m[1] as $raw) {
                $key = str_replace(['\\'.$quote, '\\\\'], [$quote, '\\'], $raw);
                // Kunci grup Laravel (auth.failed, validation.*) ada di lang/{kode}/*.php, bukan JSON.
                if (! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)) {
                    $found[] = $key;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Kunci yang belum diterjemahkan per bahasa (kecuali bahasa sumber "id").
     *
     * @return array<string, list<string>>
     */
    public static function missing(): array
    {
        $keys = array_keys(static::collect());
        $missing = [];

        foreach (array_keys(Locales::all()) as $locale) {
            if ($locale === 'id') {
                continue;
            }
            $path = lang_path("{$locale}.json");
            $dict = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
            $missing[$locale] = array_values(array_filter($keys, fn ($k) => ! array_key_exists($k, $dict)));
        }

        return $missing;
    }

    /**
     * Terjemahan yang placeholder-nya (:count, :title, …) tidak sama dengan teks sumber.
     *
     * @return array<string, list<string>> bahasa => kunci bermasalah
     */
    public static function placeholderMismatches(): array
    {
        $names = function (string $text): array {
            preg_match_all('/:([a-z_]+)/', $text, $m);
            $list = array_unique($m[1]);
            sort($list);

            return $list;
        };

        $result = [];
        foreach (array_keys(Locales::all()) as $locale) {
            $path = lang_path("{$locale}.json");
            if ($locale === 'id' || ! is_file($path)) {
                continue;
            }
            foreach (json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) as $key => $value) {
                // Bentuk jamak "{1} :count book|[2,*] :count books": periksa tiap bentuk.
                foreach (explode('|', $value) as $form) {
                    if ($names($key) !== $names(preg_replace('/^\s*(\{\d+\}|\[[\d*]+,\s*[\d*]+\])\s*/', '', $form))) {
                        $result[$locale][] = $key;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    private static function matchesType(string $name, string $type): bool
    {
        return match ($type) {
            'php' => str_ends_with($name, '.php'),
            'js' => str_ends_with($name, '.js'),
            'kt' => str_ends_with($name, '.kt'),
        };
    }
}
