package id.skyvora.kyread.ui.screens.product

import android.content.ClipData
import android.content.Intent
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.ConfirmationNumber
import androidx.compose.material.icons.outlined.ContentCopy
import androidx.compose.material.icons.outlined.FavoriteBorder
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.ShoppingBag
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Badge
import id.skyvora.kyread.ui.components.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import id.skyvora.kyread.ui.components.IconButton
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.MaterialTheme
import id.skyvora.kyread.ui.components.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.ClipEntry
import androidx.compose.ui.platform.LocalClipboard
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.ui.components.UserAvatar
import id.skyvora.kyread.BuildConfig
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.data.ProductDetail
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.BookCard
import id.skyvora.kyread.ui.components.BookCover
import id.skyvora.kyread.ui.components.DiscountBadge
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.QuantityStepper
import id.skyvora.kyread.ui.components.RatingStars
import id.skyvora.kyread.ui.components.formatDate
import id.skyvora.kyread.ui.components.rupiah
import id.skyvora.kyread.ui.kyViewModel
import id.skyvora.kyread.ui.navigateTab
import id.skyvora.kyread.ui.theme.KyStar
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class ProductViewModel(private val repo: KyreadRepository, private val slug: String) : ViewModel() {
    private val _state = MutableStateFlow<Load<ProductDetail>>(Load.Loading)
    val state = _state.asStateFlow()

    var adding by mutableStateOf(false)
        private set

    /** null = belum dimuat; diperbarui optimistis saat tombol hati ditekan. */
    var wishlisted by mutableStateOf<Boolean?>(null)
        private set

    init {
        load()
    }

    fun load() = viewModelScope.launch {
        _state.value = Load.Loading
        _state.value = runCatching { repo.product(slug) }
            .onSuccess { wishlisted = it.data.isWishlisted }
            .fold({ Load.Ready(it) }, { Load.Error(it.message ?: "Terjadi kesalahan") })
    }

    /** Mengembalikan pesan untuk snackbar. */
    suspend fun toggleWishlist(): String {
        val target = !(wishlisted ?: false)
        wishlisted = target
        return runCatching { repo.setWishlisted(slug, target) }
            .fold(
                { if (target) "Disimpan ke wishlist." else "Dihapus dari wishlist." },
                { wishlisted = !target; it.message ?: "Gagal memperbarui wishlist" },
            )
    }

    /** Mengembalikan pesan untuk snackbar. */
    suspend fun addToCart(product: Product, quantity: Int): Result<String> {
        adding = true
        return runCatching { repo.addToCart(product.id, quantity); "\"${product.title}\" ditambahkan ke keranjang." }
            .also { adding = false }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProductScreen(repo: KyreadRepository, nav: NavHostController, slug: String) {
    val vm = kyViewModel(key = "product-$slug") { ProductViewModel(repo, slug) }
    val state by vm.state.collectAsStateWithLifecycle()
    val user by repo.user.collectAsStateWithLifecycle()
    val cartCount by repo.cartCount.collectAsStateWithLifecycle()
    val snackbar = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    var quantity by remember { mutableStateOf(1) }
    val clipboard = LocalClipboard.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = {},
                navigationIcon = { BackIcon { nav.popBackStack() } },
                actions = {
                    val product = (state as? Load.Ready)?.data?.data
                    if (user != null && product != null && !product.isOwnProduct) {
                        val wished = vm.wishlisted == true
                        IconButton(onClick = { scope.launch { snackbar.showSnackbar(vm.toggleWishlist()) } }) {
                            Icon(
                                if (wished) Icons.Filled.Favorite else Icons.Outlined.FavoriteBorder,
                                contentDescription = if (wished) "Hapus dari wishlist" else "Simpan ke wishlist",
                                tint = if (wished) Color(0xFFEF4444) else LocalContentColor.current,
                            )
                        }
                    }
                    IconButton(onClick = {
                        val webUrl = BuildConfig.API_BASE_URL.substringBefore("/api/") + "/books/$slug"
                        context.startActivity(
                            Intent.createChooser(
                                Intent(Intent.ACTION_SEND).setType("text/plain").putExtra(Intent.EXTRA_TEXT, webUrl),
                                "Bagikan buku",
                            ),
                        )
                    }) { Icon(Icons.Filled.Share, "Bagikan") }
                    IconButton(onClick = { nav.navigateTab(Routes.CART) }) {
                        BadgedBox(badge = { if (cartCount > 0) Badge { Text("$cartCount") } }) {
                            Icon(Icons.Outlined.ShoppingBag, "Keranjang")
                        }
                    }
                },
            )
        },
        snackbarHost = { SnackbarHost(snackbar) },
        bottomBar = {
            val product = (state as? Load.Ready)?.data?.data
            if (product != null) Surface(tonalElevation = 3.dp, shadowElevation = 8.dp) {
                Column(Modifier.navigationBarsPadding().padding(16.dp)) {
                    when {
                        user == null -> Button(onClick = { nav.navigate(Routes.LOGIN) }, modifier = Modifier.fillMaxWidth()) {
                            Text("Masuk untuk membeli")
                        }
                        product.isOwnProduct -> Text(
                            "Ini buku dari tokomu sendiri. Kelola lewat kyreadSeller di web.",
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        product.stock == 0 -> Button(onClick = {}, enabled = false, modifier = Modifier.fillMaxWidth()) { Text("Stok habis") }
                        else -> {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                QuantityStepper(quantity, product.stock, onChange = { quantity = it })
                                Spacer(Modifier.width(12.dp))
                                Text("Stok ${product.stock}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Spacer(Modifier.height(10.dp))
                            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                                OutlinedButton(
                                    onClick = {
                                        scope.launch {
                                            vm.addToCart(product, quantity)
                                                .onSuccess { snackbar.showSnackbar(it) }
                                                .onFailure { snackbar.showSnackbar(it.message ?: "Gagal") }
                                        }
                                    },
                                    enabled = !vm.adding,
                                    modifier = Modifier.weight(1f),
                                ) { Text("+ Keranjang") }
                                Button(
                                    onClick = {
                                        scope.launch {
                                            vm.addToCart(product, quantity)
                                                .onSuccess { nav.navigate(Routes.CHECKOUT) }
                                                .onFailure { snackbar.showSnackbar(it.message ?: "Gagal") }
                                        }
                                    },
                                    enabled = !vm.adding,
                                    modifier = Modifier.weight(1f),
                                ) { Text("Beli Sekarang") }
                            }
                        }
                    }
                }
            }
        },
    ) { padding ->
        when (val s = state) {
            Load.Loading -> LoadingBox(Modifier.padding(padding))
            is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            is Load.Ready -> ProductContent(s.data, nav, Modifier.padding(padding)) { code ->
                scope.launch {
                    clipboard.setClipEntry(ClipEntry(ClipData.newPlainText("Voucher", code)))
                    snackbar.showSnackbar("Kode $code disalin. Tempel di halaman checkout.")
                }
            }
        }
    }
}

@Composable
private fun ProductContent(detail: ProductDetail, nav: NavHostController, modifier: Modifier, onCopyVoucher: (String) -> Unit) {
    val p = detail.data
    LazyColumn(modifier) {
        item {
            BookCover(p.imageUrl, Modifier.fillMaxWidth().height(340.dp).padding(vertical = 16.dp))
        }
        item {
            Column(Modifier.padding(horizontal = 16.dp)) {
                p.category?.let { Text(it.name, color = MaterialTheme.colorScheme.primary, style = MaterialTheme.typography.labelLarge) }
                Text(p.title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                Text("oleh ${p.author}", color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(Modifier.height(6.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    if (p.rating != null) {
                        Icon(Icons.Filled.Star, null, tint = KyStar, modifier = Modifier.size(18.dp))
                        Text(" ${p.rating} (${p.reviewsCount} ulasan) · ", style = MaterialTheme.typography.bodySmall)
                    }
                    Text("${p.soldCount} terjual", style = MaterialTheme.typography.bodySmall)
                }
                Spacer(Modifier.height(10.dp))
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(rupiah(p.price), style = MaterialTheme.typography.headlineMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                    if (p.discountPercent > 0 && p.compareAtPrice != null) {
                        DiscountBadge(p.discountPercent)
                        Text(
                            rupiah(p.compareAtPrice),
                            style = MaterialTheme.typography.bodyMedium.copy(textDecoration = TextDecoration.LineThrough),
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
                if (p.vouchers.isNotEmpty()) {
                    Spacer(Modifier.height(12.dp))
                    Text("Voucher toko · ketuk untuk menyalin", style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(Modifier.height(6.dp))
                    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        p.vouchers.forEach { v ->
                            Surface(
                                onClick = { onCopyVoucher(v.code) },
                                shape = RoundedCornerShape(10.dp),
                                color = MaterialTheme.colorScheme.secondaryContainer,
                                border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = .5f)),
                            ) {
                                Row(Modifier.padding(horizontal = 12.dp, vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Outlined.ConfirmationNumber, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(18.dp))
                                    Spacer(Modifier.width(8.dp))
                                    Text(v.code, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                                    Spacer(Modifier.width(8.dp))
                                    Text(v.summary, style = MaterialTheme.typography.bodySmall, modifier = Modifier.weight(1f))
                                    Icon(Icons.Outlined.ContentCopy, "Salin", modifier = Modifier.size(16.dp))
                                }
                            }
                        }
                    }
                }
            }
        }
        p.store?.let { store ->
            item {
                Card(
                    Modifier.fillMaxWidth().padding(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                ) {
                    Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                        UserAvatar(store.name, store.logoUrl, size = 44)
                        Spacer(Modifier.width(12.dp))
                        Column {
                            Text(store.name, fontWeight = FontWeight.SemiBold)
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Outlined.LocationOn, null, modifier = Modifier.size(14.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text(store.city.orEmpty(), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                    }
                }
            }
        }
        item {
            Column(Modifier.padding(horizontal = 16.dp)) {
                Text("Deskripsi", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(4.dp))
                Text(p.description.orEmpty())
                Spacer(Modifier.height(16.dp))
                Text("Detail Buku", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                listOfNotNull(
                    "Penulis" to p.author,
                    p.publisher?.let { "Penerbit" to it },
                    p.publishedYear?.let { "Tahun terbit" to "$it" },
                    p.pages?.let { "Halaman" to "$it" },
                    p.isbn?.let { "ISBN" to it },
                    "Berat" to "${p.weight} gram",
                ).forEach { (k, v) ->
                    Row(Modifier.padding(vertical = 4.dp)) {
                        Text(k, modifier = Modifier.width(120.dp), color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Text(v)
                    }
                    HorizontalDivider()
                }
            }
        }
        item {
            Text(
                "Ulasan Pembeli",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(start = 16.dp, top = 20.dp, bottom = 4.dp),
            )
        }
        if (detail.reviews.isEmpty()) {
            item { Text("Belum ada ulasan.", color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.padding(horizontal = 16.dp)) }
        }
        items(detail.reviews, key = { it.id }) { r ->
            Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(r.userName ?: "Pembeli", fontWeight = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                    Text(formatDate(r.createdAt), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                RatingStars(r.rating, size = 14)
                r.comment?.let { Text(it, style = MaterialTheme.typography.bodyMedium) }
            }
        }
        if (detail.related.isNotEmpty()) {
            item {
                Text(
                    "Buku Serupa",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(start = 16.dp, top = 20.dp, bottom = 8.dp),
                )
                LazyRow(
                    contentPadding = androidx.compose.foundation.layout.PaddingValues(horizontal = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    items(detail.related, key = { it.id }) { r ->
                        BookCard(r, onClick = { nav.navigate(Routes.product(r.slug)) }, modifier = Modifier.width(160.dp))
                    }
                }
                Spacer(Modifier.height(24.dp))
            }
        }
    }
}
