package id.skyvora.kyread.ui.components

import id.skyvora.kyread.i18n.tr
import id.skyvora.kyread.i18n.trc
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarBorder
import androidx.compose.material.icons.outlined.CloudOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import id.skyvora.kyread.BuildConfig
import id.skyvora.kyread.R
import id.skyvora.kyread.data.OrderStatus
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.ui.theme.KyStar

private val KyDanger = Color(0xFFEF4444)

/** Badge "STAGING" untuk build yang mengarah ke server uji coba; tidak tampil di build produksi. */
@Composable
fun EnvBadge(modifier: Modifier = Modifier) {
    if (!BuildConfig.IS_STAGING) return
    Surface(color = Color(0xFFF59E0B), contentColor = Color.White, shape = RoundedCornerShape(6.dp), modifier = modifier) {
        Text(
            "STAGING",
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
        )
    }
}

/** Label "-20%" merah untuk buku yang sedang diskon. */
@Composable
fun DiscountBadge(percent: Int, modifier: Modifier = Modifier) {
    Surface(color = KyDanger, contentColor = Color.White, shape = RoundedCornerShape(6.dp), modifier = modifier) {
        Text("-$percent%", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
    }
}

/** Harga sebelum diskon (dicoret). */
@Composable
fun StrikePrice(price: Long, modifier: Modifier = Modifier) {
    Text(
        rupiah(price),
        style = MaterialTheme.typography.labelSmall.copy(textDecoration = TextDecoration.LineThrough),
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        modifier = modifier,
    )
}

/** State pemuatan sederhana untuk layar. */
sealed interface Load<out T> {
    data object Loading : Load<Nothing>
    data class Error(val message: String) : Load<Nothing>
    data class Ready<T>(val data: T) : Load<T>
}

@Composable
fun BookCover(url: String, modifier: Modifier = Modifier, contentScale: ContentScale = ContentScale.Fit) {
    AsyncImage(
        model = url,
        contentDescription = null,
        contentScale = contentScale,
        modifier = modifier.background(MaterialTheme.colorScheme.surfaceVariant),
    )
}

@Composable
fun BookCard(product: Product, onClick: () -> Unit, modifier: Modifier = Modifier) {
    val (source, scale) = rememberPressScale(0.96f)
    Card(
        onClick = onClick,
        interactionSource = source,
        modifier = modifier.then(scale).fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Box {
            BookCover(
                url = product.imageUrl,
                modifier = Modifier
                    .fillMaxWidth()
                    .aspectRatio(3f / 4f)
                    .padding(10.dp),
            )
            if (product.discountPercent > 0) {
                DiscountBadge(product.discountPercent, Modifier.align(Alignment.TopStart).padding(8.dp))
            }
        }
        Column(Modifier.padding(horizontal = 12.dp, vertical = 10.dp)) {
            product.category?.let {
                Text(it.name, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.primary)
            }
            Text(
                product.title,
                style = MaterialTheme.typography.titleSmall,
                maxLines = 2,
                minLines = 2,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                product.author,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Spacer(Modifier.height(6.dp))
            Text(rupiah(product.price), fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
            product.compareAtPrice?.let { StrikePrice(it) }
            Row(verticalAlignment = Alignment.CenterVertically) {
                product.rating?.let {
                    Icon(Icons.Filled.Star, null, tint = KyStar, modifier = Modifier.size(14.dp))
                    Text(" $it · ", style = MaterialTheme.typography.labelSmall)
                }
                Text(
                    if (product.stock == 0) tr("Stok habis") else trc(":count terjual", product.soldCount),
                    style = MaterialTheme.typography.labelSmall,
                    color = if (product.stock == 0) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
fun RatingStars(rating: Int, modifier: Modifier = Modifier, size: Int = 16, onChange: ((Int) -> Unit)? = null) {
    Row(modifier) {
        (1..5).forEach { i ->
            val icon = if (i <= rating) Icons.Filled.Star else Icons.Filled.StarBorder
            val m = Modifier.size(size.dp).let { if (onChange != null) it.clickable { onChange(i) } else it }
            Icon(icon, contentDescription = tr(":n bintang", "n" to i), tint = KyStar, modifier = m)
        }
    }
}

@Composable
fun QuantityStepper(value: Int, max: Int, onChange: (Int) -> Unit, modifier: Modifier = Modifier) {
    Row(modifier, verticalAlignment = Alignment.CenterVertically) {
        FilledTonalIconButton(onClick = { onChange(value - 1) }, enabled = value > 1, modifier = Modifier.size(34.dp)) {
            Icon(Icons.Filled.Remove, tr("Kurangi"), modifier = Modifier.size(18.dp))
        }
        Text("$value", modifier = Modifier.width(36.dp), textAlign = TextAlign.Center, fontWeight = FontWeight.SemiBold)
        FilledTonalIconButton(onClick = { onChange(value + 1) }, enabled = value < max, modifier = Modifier.size(34.dp)) {
            Icon(Icons.Filled.Add, tr("Tambah"), modifier = Modifier.size(18.dp))
        }
    }
}

@Composable
fun StatusChip(status: String, label: String) {
    val (bg, fg) = when (OrderStatus.from(status)) {
        OrderStatus.Pending -> Color(0xFFFFF3CD) to Color(0xFF8A6100)
        OrderStatus.Paid -> Color(0xFFD7F2FB) to Color(0xFF055160)
        OrderStatus.Processing -> Color(0xFFD9ECFF) to Color(0xFF0B4A80)
        OrderStatus.Shipped -> Color(0xFFE7E3FF) to Color(0xFF3D2C8D)
        OrderStatus.Completed -> Color(0xFFD1F2DD) to Color(0xFF0F5132)
        OrderStatus.Cancelled -> Color(0xFFF8D7DA) to Color(0xFF842029)
        null -> MaterialTheme.colorScheme.surfaceVariant to MaterialTheme.colorScheme.onSurfaceVariant
    }
    Surface(color = bg, shape = RoundedCornerShape(50)) {
        Text(label, color = fg, style = MaterialTheme.typography.labelMedium, modifier = Modifier.padding(horizontal = 10.dp, vertical = 3.dp))
    }
}

@Composable
fun LoadingBox(modifier: Modifier = Modifier, message: String = tr("Memuat…")) {
    Column(
        modifier.fillMaxSize(),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        KyLottie("loading", Modifier.size(140.dp))
        Text(message, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

@Composable
fun MessageBox(
    icon: ImageVector,
    title: String,
    modifier: Modifier = Modifier,
    body: String? = null,
    actionLabel: String? = null,
    onAction: (() -> Unit)? = null,
    animation: String? = null,
) {
    Column(
        modifier.fillMaxSize().padding(32.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        if (animation != null) {
            KyLottie(animation, Modifier.size(180.dp))
        } else {
            Box(
                Modifier.size(88.dp).clip(RoundedCornerShape(50)).background(MaterialTheme.colorScheme.primaryContainer),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icon, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(44.dp))
            }
        }
        Spacer(Modifier.height(16.dp))
        Text(title, style = MaterialTheme.typography.titleMedium, textAlign = TextAlign.Center)
        body?.let {
            Spacer(Modifier.height(4.dp))
            Text(it, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center)
        }
        if (actionLabel != null && onAction != null) {
            Spacer(Modifier.height(16.dp))
            Button(onClick = onAction) { Text(actionLabel) }
        }
    }
}

@Composable
fun ErrorBox(message: String, onRetry: () -> Unit, modifier: Modifier = Modifier) {
    MessageBox(Icons.Outlined.CloudOff, tr("Gagal memuat"), modifier, body = message, actionLabel = tr("Coba lagi"), onAction = onRetry, animation = "error")
}

@Composable
fun SectionTitle(text: String, modifier: Modifier = Modifier, action: String? = null, onAction: (() -> Unit)? = null) {
    Row(modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
        Text(text, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
        if (action != null && onAction != null) {
            OutlinedButton(onClick = onAction, contentPadding = androidx.compose.foundation.layout.PaddingValues(horizontal = 12.dp)) {
                Text(action, style = MaterialTheme.typography.labelMedium)
            }
        }
    }
}

@Composable
fun SummaryRow(label: String, value: String, bold: Boolean = false, valueColor: Color = Color.Unspecified) {
    Row(Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
        Text(
            label,
            modifier = Modifier.weight(1f),
            color = if (bold) Color.Unspecified else MaterialTheme.colorScheme.onSurfaceVariant,
            fontWeight = if (bold) FontWeight.Bold else null,
        )
        Text(value, fontWeight = if (bold) FontWeight.Bold else FontWeight.Medium, color = valueColor)
    }
}

/** Avatar bulat: inisial nama sebagai dasar, foto (bila ada & berhasil dimuat) di atasnya. */
@Composable
fun UserAvatar(name: String, url: String?, modifier: Modifier = Modifier, size: Int = 64) {
    val initials = name.split(" ").filter { it.isNotBlank() }.take(2).joinToString("") { it.first().uppercase() }.ifEmpty { "?" }
    Box(
        modifier.size(size.dp).clip(RoundedCornerShape(50)).background(MaterialTheme.colorScheme.primary),
        contentAlignment = Alignment.Center,
    ) {
        Text(initials, color = MaterialTheme.colorScheme.onPrimary, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
        // Avatar default dari layanan inisial eksternal tidak perlu dimuat; inisial lokal sudah cukup.
        if (url != null && !url.contains("ui-avatars.com")) {
            AsyncImage(model = url, contentDescription = null, contentScale = ContentScale.Crop, modifier = Modifier.fillMaxSize())
        }
    }
}

@Composable
fun BackIcon(onBack: () -> Unit) {
    IconButton(onClick = onBack) {
        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = tr("Kembali"))
    }
}
