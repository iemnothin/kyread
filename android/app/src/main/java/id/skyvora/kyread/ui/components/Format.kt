package id.skyvora.kyread.ui.components

import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

private val IdLocale: Locale = Locale.forLanguageTag("id-ID")
private val RupiahFormat = DecimalFormat("#,###", DecimalFormatSymbols(IdLocale))
private val DateTimeFmt = DateTimeFormatter.ofPattern("d MMM yyyy, HH:mm", IdLocale)
private val DateFmt = DateTimeFormatter.ofPattern("d MMM yyyy", IdLocale)

/** 73000 -> "Rp 73.000" */
fun rupiah(amount: Long): String = "Rp " + RupiahFormat.format(amount)

private fun parse(iso: String?) = iso?.let {
    runCatching { OffsetDateTime.parse(it).atZoneSameInstant(ZoneId.systemDefault()) }.getOrNull()
}

/** ISO-8601 dari API -> "12 Nov 2026, 14:30" (zona waktu perangkat). */
fun formatDateTime(iso: String?): String = parse(iso)?.format(DateTimeFmt) ?: "-"

fun formatDate(iso: String?): String = parse(iso)?.format(DateFmt) ?: "-"
