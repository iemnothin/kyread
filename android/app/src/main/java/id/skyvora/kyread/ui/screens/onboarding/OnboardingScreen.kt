package id.skyvora.kyread.ui.screens.onboarding

import android.app.Activity
import android.content.Context
import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.StartOffset
import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.systemBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.PagerState
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.automirrored.filled.MenuBook
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.LocalOffer
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.Translate
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.graphics.lerp
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.view.WindowCompat
import id.skyvora.kyread.i18n.I18n
import id.skyvora.kyread.i18n.tr
import id.skyvora.kyread.ui.components.Button
import id.skyvora.kyread.ui.components.KyLottie
import id.skyvora.kyread.ui.components.KyraMascot
import id.skyvora.kyread.ui.components.LanguageDialog
import id.skyvora.kyread.ui.components.TextButton
import kotlinx.coroutines.launch
import kotlin.math.absoluteValue

/** Onboarding tampil sekali saat aplikasi pertama dibuka. */
object Onboarding {
    private const val PREFS = "settings"
    private const val KEY = "onboarding_done"

    fun isDone(context: Context) = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).getBoolean(KEY, false)

    fun markDone(context: Context) = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit().putBoolean(KEY, true).apply()
}

private data class Page(val title: String, val body: String, val colors: List<Color>)

// Warna latar per halaman; saat digeser, warna berbaur mulus ke halaman berikutnya.
private val PageColors = listOf(
    listOf(Color(0xFF1D4ED8), Color(0xFF43AAFF), Color(0xFF7C3AED)),
    listOf(Color(0xFF0E7490), Color(0xFF06B6D4), Color(0xFF3B82F6)),
    listOf(Color(0xFFDB2777), Color(0xFFF97316), Color(0xFF8B5CF6)),
    listOf(Color(0xFF047857), Color(0xFF22C55E), Color(0xFF0EA5E9)),
)

/** @param onFinish dipanggil dengan true bila pengguna memilih "Masuk". */
@Composable
fun OnboardingScreen(onFinish: (signIn: Boolean) -> Unit) {
    val context = LocalContext.current
    val pages = listOf(
        Page(tr("Halo, aku Kyra!"), tr("Temukan ribuan buku dari berbagai toko dalam satu aplikasi."), PageColors[0]),
        Page(tr("Cari buku semudah mengetik"), tr("Saran kata muncul per huruf, cari pakai foto sampul, atau tanya Kyra."), PageColors[1]),
        Page(tr("Hemat dengan voucher & diskon"), tr("Simpan buku ke wishlist, pakai voucher toko, dan intip harga coret."), PageColors[2]),
        Page(tr("Pesan, bayar, dan lacak"), tr("Notifikasi status pesanan dan invoice selalu ada di genggamanmu."), PageColors[3]),
    )
    val pager = rememberPagerState { pages.size }
    val scope = rememberCoroutineScope()
    var pickLanguage by remember { mutableStateOf(false) }
    val last = pager.currentPage == pages.lastIndex

    // Ikon status bar putih di atas latar gelap selama onboarding.
    val view = LocalView.current
    DisposableEffect(Unit) {
        val window = (view.context as? Activity)?.window
        val controller = window?.let { WindowCompat.getInsetsController(it, view) }
        val previous = controller?.isAppearanceLightStatusBars
        controller?.isAppearanceLightStatusBars = false
        onDispose { if (previous != null) controller.isAppearanceLightStatusBars = previous }
    }

    if (pickLanguage) {
        LanguageDialog(onDismiss = { pickLanguage = false }) { code ->
            pickLanguage = false
            I18n.change(context, code, reopenAccount = false)
        }
    }

    Box(Modifier.fillMaxSize().background(pagerBrush(pager))) {
        FloatingIcons(pager)

        Column(Modifier.fillMaxSize().systemBarsPadding()) {
            // Bilah atas: bahasa & lewati
            Row(Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    onClick = { pickLanguage = true },
                    shape = RoundedCornerShape(50),
                    color = Color.White.copy(alpha = .2f),
                    contentColor = Color.White,
                ) {
                    Row(Modifier.padding(horizontal = 12.dp, vertical = 6.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Outlined.Translate, null, Modifier.size(18.dp))
                        Spacer(Modifier.width(6.dp))
                        Text(I18n.language.native, style = MaterialTheme.typography.labelLarge)
                    }
                }
                Spacer(Modifier.weight(1f))
                if (!last) {
                    TextButton(onClick = { Onboarding.markDone(context); onFinish(false) }) {
                        Text(tr("Lewati"), color = Color.White, fontWeight = FontWeight.SemiBold)
                    }
                }
            }

            HorizontalPager(pager, Modifier.weight(1f)) { index ->
                val offset = ((pager.currentPage - index) + pager.currentPageOffsetFraction).absoluteValue.coerceIn(0f, 1f)
                Column(
                    Modifier
                        .fillMaxSize()
                        .padding(horizontal = 28.dp)
                        .graphicsLayer {
                            // Halaman yang digeser mengecil & memudar sedikit.
                            alpha = 1f - offset * .6f
                            val s = 1f - offset * .12f
                            scaleX = s
                            scaleY = s
                        },
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center,
                ) {
                    Illustration(index, Modifier.size(260.dp))
                    Spacer(Modifier.height(24.dp))
                    Text(
                        pages[index].title, color = Color.White, fontSize = 28.sp, fontWeight = FontWeight.ExtraBold,
                        textAlign = TextAlign.Center, lineHeight = 34.sp,
                    )
                    Spacer(Modifier.height(12.dp))
                    Text(
                        pages[index].body, color = Color.White.copy(alpha = .88f), style = MaterialTheme.typography.bodyLarge,
                        textAlign = TextAlign.Center,
                    )
                }
            }

            // Indikator halaman
            Row(Modifier.fillMaxWidth().padding(vertical = 16.dp), horizontalArrangement = Arrangement.Center) {
                pages.indices.forEach { i ->
                    val active = pager.currentPage == i
                    val width by animateDpAsState(if (active) 28.dp else 8.dp, tween(300), label = "dot")
                    Box(
                        Modifier
                            .padding(horizontal = 4.dp)
                            .height(8.dp)
                            .width(width)
                            .clip(CircleShape)
                            .background(Color.White.copy(alpha = if (active) 1f else .45f)),
                    )
                }
            }

            // Tombol bawah
            Column(Modifier.fillMaxWidth().padding(horizontal = 24.dp).padding(bottom = 16.dp)) {
                Button(
                    onClick = {
                        if (last) {
                            Onboarding.markDone(context)
                            onFinish(false)
                        } else {
                            scope.launch { pager.animateScrollToPage(pager.currentPage + 1) }
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(54.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = pages[pager.currentPage].colors[0]),
                ) {
                    AnimatedContent(last, transitionSpec = { fadeIn(tween(200)) togetherWith fadeOut(tween(200)) }, label = "cta") { isLast ->
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(if (isLast) tr("Mulai Belanja") else tr("Lanjut"), fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Spacer(Modifier.width(8.dp))
                            Icon(Icons.AutoMirrored.Filled.ArrowForward, null)
                        }
                    }
                }
                Box(Modifier.fillMaxWidth().height(48.dp), contentAlignment = Alignment.Center) {
                    if (last) {
                        TextButton(onClick = { Onboarding.markDone(context); onFinish(true) }) {
                            Text(tr("Sudah punya akun? Masuk"), color = Color.White, fontWeight = FontWeight.SemiBold)
                        }
                    }
                }
            }
        }
    }
}

/** Gradien latar yang berbaur mengikuti posisi geser + bergerak pelan sendiri. */
@Composable
private fun pagerBrush(pager: PagerState): Brush {
    val position = (pager.currentPage + pager.currentPageOffsetFraction).coerceIn(0f, (PageColors.size - 1).toFloat())
    val from = position.toInt()
    val to = (from + 1).coerceAtMost(PageColors.lastIndex)
    val f = position - from
    val colors = PageColors[from].indices.map { lerp(PageColors[from][it], PageColors[to][it], f) }

    val drift by rememberInfiniteTransition(label = "bg").animateFloat(
        0f, 1f, infiniteRepeatable(tween(9000, easing = FastOutSlowInEasing), RepeatMode.Reverse), label = "drift",
    )
    return Brush.linearGradient(
        colors,
        start = androidx.compose.ui.geometry.Offset(0f, 300f * drift),
        end = androidx.compose.ui.geometry.Offset(1400f, 2600f - 400f * drift),
    )
}

private data class Floater(val icon: ImageVector, val x: Float, val size: Int, val duration: Int, val delay: Int, val turn: Float)

private val Floaters = listOf(
    Floater(Icons.AutoMirrored.Filled.MenuBook, .08f, 34, 11000, 0, 40f),
    Floater(Icons.Filled.Star, .22f, 18, 8000, 2500, -90f),
    Floater(Icons.Filled.Bookmark, .36f, 26, 12500, 5000, 60f),
    Floater(Icons.Filled.Favorite, .5f, 20, 9000, 1200, 120f),
    Floater(Icons.Filled.ConfirmationNumber, .64f, 30, 13000, 7000, -50f),
    Floater(Icons.Filled.AutoAwesome, .78f, 22, 9500, 3800, 90f),
    Floater(Icons.Filled.LocalOffer, .9f, 26, 11500, 6200, -30f),
    Floater(Icons.AutoMirrored.Filled.MenuBook, .3f, 20, 14000, 9000, 20f),
    Floater(Icons.Filled.Star, .7f, 14, 10500, 8200, 150f),
)

/** Ikon putih transparan yang melayang naik; ikut bergeser (parallax) saat halaman digeser. */
@Composable
private fun FloatingIcons(pager: PagerState) {
    val t = rememberInfiniteTransition(label = "floaters")
    val parallax = (pager.currentPage + pager.currentPageOffsetFraction) * -40f
    BoxWithConstraints(Modifier.fillMaxSize()) {
        val h = maxHeight
        val w = maxWidth
        Floaters.forEach { f ->
            val progress by t.animateFloat(
                0f, 1f,
                infiniteRepeatable(tween(f.duration, easing = LinearEasing), initialStartOffset = StartOffset(f.delay)),
                label = "float",
            )
            Icon(
                f.icon, null,
                tint = Color.White.copy(alpha = .28f * (if (progress < .1f) progress * 10 else if (progress > .85f) (1 - progress) / .15f else 1f)),
                modifier = Modifier
                    .offset(x = w * f.x + parallax.dp, y = h * (1.05f - progress * 1.2f))
                    .size(f.size.dp)
                    .rotate(progress * f.turn),
            )
        }
    }
}

@Composable
private fun Illustration(index: Int, modifier: Modifier) {
    Box(modifier, contentAlignment = Alignment.Center) {
        // Lingkaran cahaya di belakang ilustrasi
        val pulse by rememberInfiniteTransition(label = "glow").animateFloat(
            .9f, 1.05f, infiniteRepeatable(tween(1800, easing = FastOutSlowInEasing), RepeatMode.Reverse), label = "pulse",
        )
        Box(
            Modifier.size(230.dp).graphicsLayer { scaleX = pulse; scaleY = pulse }
                .clip(CircleShape).background(Color.White.copy(alpha = .16f)),
        )
        Box(Modifier.size(170.dp).clip(CircleShape).background(Color.White.copy(alpha = .14f)))
        when (index) {
            0 -> KyraMascot(Modifier.size(200.dp), waving = true)
            1 -> KyLottie("empty", Modifier.size(230.dp))
            2 -> VoucherStack()
            else -> KyLottie("success", Modifier.size(200.dp))
        }
    }
}

/** Kartu voucher, badge diskon, dan hati yang melayang bergantian. */
@Composable
private fun VoucherStack() {
    val t = rememberInfiniteTransition(label = "vouchers")
    val bob by t.animateFloat(-6f, 6f, infiniteRepeatable(tween(1400, easing = FastOutSlowInEasing), RepeatMode.Reverse), label = "bob")
    val bob2 by t.animateFloat(6f, -6f, infiniteRepeatable(tween(1700, easing = FastOutSlowInEasing), RepeatMode.Reverse), label = "bob2")
    Box(Modifier.size(240.dp)) {
        Surface(
            Modifier.align(Alignment.Center).offset(y = bob.dp).rotate(-8f),
            shape = RoundedCornerShape(18.dp), color = Color.White, shadowElevation = 8.dp,
        ) {
            Row(Modifier.padding(horizontal = 18.dp, vertical = 16.dp), verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Filled.ConfirmationNumber, null, tint = Color(0xFFDB2777), modifier = Modifier.size(34.dp))
                Spacer(Modifier.width(10.dp))
                Column {
                    Text("KYREAD10", fontWeight = FontWeight.ExtraBold, color = Color(0xFF1F2937), fontSize = 18.sp)
                    Text(tr("Diskon :percent%", "percent" to 10), color = Color(0xFF6B7280), style = MaterialTheme.typography.bodySmall)
                }
            }
        }
        Surface(
            Modifier.align(Alignment.TopEnd).offset(x = (-6).dp, y = (20 + bob2).dp).rotate(12f),
            shape = RoundedCornerShape(12.dp), color = Color(0xFFEF4444),
        ) {
            Text("-20%", color = Color.White, fontWeight = FontWeight.ExtraBold, fontSize = 20.sp, modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp))
        }
        Surface(
            Modifier.align(Alignment.BottomStart).offset(x = 18.dp, y = (-24 + bob2).dp),
            shape = CircleShape, color = Color.White, shadowElevation = 6.dp,
        ) {
            Icon(Icons.Filled.Favorite, null, tint = Color(0xFFEF4444), modifier = Modifier.padding(12.dp).size(28.dp))
        }
    }
}
