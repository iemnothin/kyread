package id.skyvora.kyread.ui.components

import id.skyvora.kyread.i18n.I18n
import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

private val IdLocale: Locale = Locale.forLanguageTag("id-ID")
private val RupiahFormat = DecimalFormat("#,###", DecimalFormatSymbols(IdLocale))

/** 73000 -> "Rp 73.000" */
fun rupiah(amount: Long): String = "Rp " + RupiahFormat.format(amount)

private fun parse(iso: String?) = iso?.let {
    runCatching { OffsetDateTime.parse(it).atZoneSameInstant(ZoneId.systemDefault()) }.getOrNull()
}

/** ISO-8601 dari API -> "12 Nov 2026, 14:30" (zona waktu perangkat). */
fun formatDateTime(iso: String?): String =
    parse(iso)?.format(DateTimeFormatter.ofPattern("d MMM yyyy, HH:mm", I18n.locale)) ?: "-"

fun formatDate(iso: String?): String = parse(iso)?.format(DateTimeFormatter.ofPattern("d MMM yyyy", I18n.locale)) ?: "-"
