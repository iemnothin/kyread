package id.skyvora.kyread.i18n

import android.content.Context
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import kotlinx.serialization.json.Json
import java.util.Locale

/**
 * Bahasa antarmuka. Teks sumber ditulis dalam bahasa Indonesia dan dipakai sebagai kunci:
 *
 *     Text(tr("Keranjang"))
 *     Text(tr("Stok tinggal :stock.", "stock" to 3))
 *     Text(trc(":count buku", count))           // bentuk jamak
 *
 * Kamus = lang/{kode}.json milik web (disalin ke assets/i18n saat build, lihat app/build.gradle.kts),
 * jadi web dan aplikasi selalu memakai terjemahan yang sama. Daftar bahasa = config/locales.php.
 */
object I18n {
    data class Language(val code: String, val native: String, val rtl: Boolean = false, val formatTag: String = code)

    const val SOURCE = "id"

    val languages = listOf(
        Language("id", "Bahasa Indonesia", formatTag = "id-ID"),
        Language("en", "English"),
        Language("ms", "Bahasa Melayu"),
        Language("jv", "Basa Jawa", formatTag = "id-ID"),
        Language("su", "Basa Sunda", formatTag = "id-ID"),
        Language("ar", "العربية", rtl = true),
        Language("zh", "简体中文", formatTag = "zh-Hans"),
        Language("ja", "日本語"),
        Language("ko", "한국어"),
        Language("es", "Español"),
    )

    private const val PREFS = "settings"
    private const val KEY = "language"
    private val json = Json { ignoreUnknownKeys = true }

    /** State Compose: layar yang membaca tr() ikut diperbarui saat bahasa diganti. */
    var language by mutableStateOf(languages.first())
        private set
    private var dict by mutableStateOf<Map<String, String>>(emptyMap())

    /** Dipanggil saat aplikasi dibuka: pilihan tersimpan, lalu bahasa perangkat, lalu Indonesia. */
    fun init(context: Context) {
        val saved = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).getString(KEY, null)
        apply(context, saved ?: deviceLanguage())
    }

    /** True setelah pengguna mengganti bahasa di sesi ini (navigasi dibuka ulang di layar Akun). */
    var changedByUser by mutableStateOf(false)
        private set

    fun change(context: Context, code: String) {
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit().putString(KEY, code).apply()
        changedByUser = true
        apply(context, code)
    }

    private fun apply(context: Context, code: String) {
        val lang = languages.firstOrNull { it.code == code } ?: languages.first()
        dict = if (lang.code == SOURCE) emptyMap() else load(context, lang.code)
        language = lang
    }

    private fun load(context: Context, code: String): Map<String, String> = runCatching {
        context.assets.open("i18n/$code.json").bufferedReader().use { json.decodeFromString<Map<String, String>>(it.readText()) }
    }.getOrDefault(emptyMap())

    private fun deviceLanguage(): String {
        val device = Locale.getDefault().language.let { if (it == "in") "id" else it } // Java lama memakai "in" untuk Indonesia
        return languages.firstOrNull { it.code == device }?.code ?: SOURCE
    }

    val locale: Locale get() = Locale.forLanguageTag(language.formatTag)

    fun tr(key: String, params: Array<out Pair<String, Any?>>): String = replace(dict[key] ?: key, params)

    fun trc(key: String, count: Int, params: Array<out Pair<String, Any?>>): String =
        replace(choose(dict[key] ?: key, count), arrayOf(*params, "count" to count))

    private fun replace(text: String, params: Array<out Pair<String, Any?>>): String =
        // Nama terpanjang dulu agar ":count" tidak terpotong oleh ":c".
        params.sortedByDescending { it.first.length }.fold(text) { acc, (name, value) -> acc.replace(":$name", value.toString()) }

    /**
     * Pilih bentuk jamak seperti trans_choice Laravel:
     * "{1} :count book|[2,*] :count books", atau tanpa kondisi: bentuk pertama untuk 1, kedua untuk lainnya.
     */
    private fun choose(text: String, count: Int): String {
        val parts = text.split('|')
        if (parts.size == 1) return text

        for (part in parts) {
            val m = PLURAL_CONDITION.find(part) ?: continue
            val (_, exact, from, to) = m.destructured
            val matches = if (exact.isNotEmpty()) {
                count == exact.toInt()
            } else {
                (from == "*" || count >= from.toInt()) && (to == "*" || count <= to.toInt())
            }
            if (matches) return part.substring(m.range.last + 1)
        }
        val forms = parts.map { PLURAL_CONDITION.replace(it, "") }
        return if (count == 1) forms[0] else forms.getOrElse(1) { forms[0] }
    }

    // Semua kurung di-escape: regex ICU di Android menolak "}" dan "]" tanpa escape (JVM menerimanya).
    private val PLURAL_CONDITION = Regex("""^\s*(\{(\d+)\}|\[(\d+|\*),\s*(\d+|\*)\])\s*""")
}

/** Terjemahkan teks sumber (bahasa Indonesia) ke bahasa aktif. */
fun tr(key: String, vararg params: Pair<String, Any?>): String = I18n.tr(key, params)

/** Seperti [tr] dengan bentuk jamak; ":count" otomatis diisi. */
fun trc(key: String, count: Int, vararg params: Pair<String, Any?>): String = I18n.trc(key, count, params)
