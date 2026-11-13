package id.skyvora.kyread.ui.components

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.keyframes
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.rotate
import androidx.compose.ui.graphics.drawscope.scale
import androidx.compose.ui.graphics.drawscope.translate

private val CoverBlue = Color(0xFF43AAFF)
private val SpineBlue = Color(0xFF1F8AE6)
private val Ink = Color(0xFF1F2937)
private val Cheek = Color(0x73FF8FAB)

/**
 * Kyra, maskot buku kyread (sama dengan maskot di halaman login web).
 * Bernapas, berkedip, melirik ke sana kemari, dan bisa melambaikan tangan.
 */
@Composable
fun KyraMascot(modifier: Modifier = Modifier, waving: Boolean = false) {
    val t = rememberInfiniteTransition(label = "kyra")
    val breathe by t.animateFloat(0f, -4f, infiniteRepeatable(tween(1600, easing = FastOutSlowInEasing), RepeatMode.Reverse), label = "breathe")
    val blink by t.animateFloat(
        1f, 1f,
        infiniteRepeatable(keyframes { durationMillis = 3800; 1f at 3500; 0.1f at 3600; 1f at 3700 }),
        label = "blink",
    )
    val look by t.animateFloat(
        0f, 0f,
        infiniteRepeatable(keyframes { durationMillis = 6000; 0f at 0; -5f at 1200; -5f at 2200; 5f at 3400; 5f at 4400; 0f at 5400 }),
        label = "look",
    )
    val wave by t.animateFloat(-18f, 18f, infiniteRepeatable(tween(420, easing = LinearEasing), RepeatMode.Reverse), label = "wave")

    Canvas(modifier) {
        // Semua koordinat dalam ruang 200×200 lalu diskalakan ke ukuran kanvas.
        val k = size.minDimension / 200f
        scale(k, k, pivot = Offset.Zero) {
            drawOval(Color(0x1F1F2937), Offset(48f, 176f), Size(112f, 14f))
            translate(top = breathe) {
                drawRoundRect(SpineBlue, Offset(48f, 40f), Size(16f, 122f), CornerRadius(7f))
                drawRoundRect(CoverBlue, Offset(56f, 40f), Size(98f, 122f), CornerRadius(14f))
                drawRoundRect(Color.White.copy(alpha = .65f), Offset(146f, 50f), Size(8f, 102f), CornerRadius(3f))
                drawCircle(Cheek, 7f, Offset(74f, 114f))
                drawCircle(Cheek, 7f, Offset(136f, 114f))

                // Mata (kedip = skala vertikal)
                scale(1f, blink, pivot = Offset(106f, 90f)) {
                    for (x in listOf(88f, 124f)) {
                        drawCircle(Color.White, 15f, Offset(x, 90f))
                        drawCircle(Ink, 6.5f, Offset(x + look, 91f))
                        drawCircle(Color.White, 2f, Offset(x + look + 2.5f, 88.5f))
                    }
                }
                val mouth = Path().apply { moveTo(93f, 120f); quadraticTo(106f, 132f, 119f, 120f) }
                drawPath(mouth, Color.White, style = Stroke(5f, cap = StrokeCap.Round))

                // Tangan: kiri diam, kanan melambai.
                drawRoundRect(SpineBlue, Offset(62f, 150f), Size(34f, 24f), CornerRadius(12f))
                if (waving) {
                    rotate(wave, pivot = Offset(158f, 120f)) {
                        drawRoundRect(SpineBlue, Offset(150f, 78f), Size(24f, 40f), CornerRadius(12f))
                    }
                } else {
                    drawRoundRect(SpineBlue, Offset(116f, 150f), Size(34f, 24f), CornerRadius(12f))
                }
            }
        }
    }
}
