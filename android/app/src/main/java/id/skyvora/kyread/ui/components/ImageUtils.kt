package id.skyvora.kyread.ui.components

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.ImageDecoder
import android.net.Uri
import android.os.Build
import java.io.ByteArrayOutputStream

/** Sisi terpanjang foto yang dikirim ke server: cukup untuk membaca sampul, hemat kuota. */
private const val MAX_SIDE = 1568

fun Bitmap.toUploadJpeg(): ByteArray {
    val scale = minOf(1f, MAX_SIDE.toFloat() / maxOf(width, height))
    val scaled = if (scale < 1f) Bitmap.createScaledBitmap(this, (width * scale).toInt(), (height * scale).toInt(), true) else this
    return ByteArrayOutputStream().use { out ->
        scaled.compress(Bitmap.CompressFormat.JPEG, 85, out)
        out.toByteArray()
    }
}

/** Baca gambar dari galeri (Uri) lalu perkecil & kompres ke JPEG. */
fun Context.readImageForUpload(uri: Uri): ByteArray {
    val bitmap = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
        ImageDecoder.decodeBitmap(ImageDecoder.createSource(contentResolver, uri)) { decoder, info, _ ->
            decoder.allocator = ImageDecoder.ALLOCATOR_SOFTWARE
            val size = info.size
            val scale = minOf(1f, MAX_SIDE.toFloat() / maxOf(size.width, size.height))
            decoder.setTargetSize((size.width * scale).toInt().coerceAtLeast(1), (size.height * scale).toInt().coerceAtLeast(1))
        }
    } else {
        contentResolver.openInputStream(uri).use { BitmapFactory.decodeStream(it) }
            ?: throw IllegalArgumentException("Gambar tidak bisa dibaca.")
    }
    return bitmap.toUploadJpeg()
}
