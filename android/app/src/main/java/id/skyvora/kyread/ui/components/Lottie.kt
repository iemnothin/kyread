package id.skyvora.kyread.ui.components

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import com.airbnb.lottie.compose.LottieAnimation
import com.airbnb.lottie.compose.LottieCompositionSpec
import com.airbnb.lottie.compose.LottieConstants
import com.airbnb.lottie.compose.animateLottieCompositionAsState
import com.airbnb.lottie.compose.rememberLottieComposition
import kotlinx.coroutines.delay

/**
 * Animasi Lottie dari assets/lottie/{name}.json (sumbernya resources/lottie milik web,
 * disalin saat build). Nama: loading, empty, success, error.
 */
@Composable
fun KyLottie(name: String, modifier: Modifier = Modifier, loop: Boolean = true, onFinished: (() -> Unit)? = null) {
    val composition by rememberLottieComposition(LottieCompositionSpec.Asset("lottie/$name.json"))
    val progress by animateLottieCompositionAsState(
        composition,
        iterations = if (loop) LottieConstants.IterateForever else 1,
    )
    if (onFinished != null && composition != null) {
        LaunchedEffect(progress) { if (progress >= 1f) onFinished() }
    }
    LottieAnimation(composition, { progress }, modifier)
}

/** Dialog perayaan singkat (mis. setelah pesanan dibuat atau dibayar); tertutup sendiri. */
@Composable
fun SuccessDialog(message: String, onDismiss: () -> Unit) {
    // Pengaman bila animasi dimatikan di pengaturan perangkat (durasi animasi = 0).
    LaunchedEffect(Unit) {
        delay(2600)
        onDismiss()
    }
    Dialog(onDismissRequest = onDismiss) {
        Surface(shape = RoundedCornerShape(24.dp), color = MaterialTheme.colorScheme.surface) {
            Column(Modifier.padding(24.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                KyLottie("success", Modifier.size(150.dp), loop = false, onFinished = {})
                Spacer(Modifier.height(4.dp))
                Text(message, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center)
            }
        }
    }
}
