/**
 * Terjemahan untuk teks yang dibuat lewat JavaScript.
 * Kamus diisi server di layout (window.KY_I18N, lihat App\Support\Locales::JS_KEYS);
 * kunci yang tidak ada ditampilkan apa adanya (teks sumber berbahasa Indonesia).
 *
 *   t('Stok tinggal :stock.', { stock: 3 })
 */
export function t(key, params = {}) {
    let text = window.KY_I18N?.[key] ?? key;
    for (const [name, value] of Object.entries(params)) {
        text = text.replaceAll(`:${name}`, String(value));
    }
    return text;
}
