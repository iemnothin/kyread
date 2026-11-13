<?php

namespace App\Console\Commands;

use App\Support\TranslationKeys;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('i18n:check {--keys : Cetak semua kunci sebagai JSON} {--missing= : Cetak kunci yang belum ada untuk satu bahasa sebagai JSON}')]
#[Description('Periksa apakah semua teks antarmuka (web & Android) sudah diterjemahkan di lang/*.json')]
class I18nCheck extends Command
{
    public function handle(): int
    {
        if ($this->option('keys')) {
            $this->line(json_encode(array_keys(TranslationKeys::collect()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $missing = TranslationKeys::missing();

        if ($locale = $this->option('missing')) {
            $this->line(json_encode($missing[$locale] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $total = count(TranslationKeys::collect());
        $this->info("{$total} teks sumber.");
        foreach ($missing as $locale => $keys) {
            count($keys) === 0
                ? $this->line("  <fg=green>✔</> {$locale}: lengkap")
                : $this->line("  <fg=red>✘</> {$locale}: ".count($keys).' belum diterjemahkan');
        }

        $mismatches = TranslationKeys::placeholderMismatches();
        foreach ($mismatches as $locale => $keys) {
            foreach ($keys as $key) {
                $this->line("  <fg=red>✘</> {$locale}: placeholder tidak cocok untuk \"{$key}\"");
            }
        }

        return collect($missing)->flatten()->isEmpty() && $mismatches === [] ? self::SUCCESS : self::FAILURE;
    }
}
