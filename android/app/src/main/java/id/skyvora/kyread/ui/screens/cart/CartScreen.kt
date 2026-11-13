package id.skyvora.kyread.ui.screens.cart

import id.skyvora.kyread.i18n.tr
import id.skyvora.kyread.i18n.trc
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Delete
import androidx.compose.material.icons.outlined.LockPerson
import androidx.compose.material.icons.outlined.ShoppingBag
import androidx.compose.material.icons.outlined.Storefront
import id.skyvora.kyread.ui.components.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import id.skyvora.kyread.ui.components.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.Cart
import id.skyvora.kyread.data.CartItem
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BookCover
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.MessageBox
import id.skyvora.kyread.ui.components.QuantityStepper
import id.skyvora.kyread.ui.components.rupiah
import id.skyvora.kyread.ui.kyViewModel
import id.skyvora.kyread.ui.navigateTab
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class CartViewModel(private val repo: KyreadRepository) : ViewModel() {
    private val _state = MutableStateFlow<Load<Cart>>(Load.Loading)
    val state = _state.asStateFlow()
    private val _messages = MutableSharedFlow<String>(extraBufferCapacity = 4)
    val messages = _messages.asSharedFlow()
    var busy by mutableStateOf(false)
        private set

    fun load(silent: Boolean = false) = viewModelScope.launch {
        if (!silent) _state.value = Load.Loading
        _state.value = runCatching { repo.cart() }.fold({ Load.Ready(it) }, { Load.Error(it.message ?: tr("Terjadi kesalahan")) })
    }

    fun setQuantity(item: CartItem, quantity: Int) = mutate {
        repo.updateCartItem(item.id, quantity).also { cart -> cart.message?.let { _messages.tryEmit(it) } }
    }

    fun remove(item: CartItem) = mutate {
        repo.removeCartItem(item.id).also { _messages.tryEmit(tr("\":title\" dihapus.", "title" to item.product.title)) }
    }

    private fun mutate(block: suspend () -> Cart) = viewModelScope.launch {
        busy = true
        runCatching { block() }
            .onSuccess { _state.value = Load.Ready(it) }
            .onFailure { _messages.tryEmit(it.message ?: tr("Gagal memperbarui keranjang")) }
        busy = false
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CartScreen(repo: KyreadRepository, nav: NavHostController, modifier: Modifier = Modifier) {
    val user by repo.user.collectAsStateWithLifecycle()
    val vm = kyViewModel { CartViewModel(repo) }
    val state by vm.state.collectAsStateWithLifecycle()
    val snackbar = remember { SnackbarHostState() }

    LaunchedEffect(user) { if (user != null) vm.load() }
    LaunchedEffect(Unit) { vm.messages.collect { snackbar.showSnackbar(it) } }

    Scaffold(
        modifier = modifier,
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        topBar = { TopAppBar(title = { Text(tr("Keranjang")) }) },
        snackbarHost = { SnackbarHost(snackbar) },
        bottomBar = {
            val cart = (state as? Load.Ready)?.data
            if (user != null && cart != null && cart.count > 0) {
                Surface(tonalElevation = 3.dp, shadowElevation = 8.dp) {
                    Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(trc("Total (:count buku)", cart.count), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Text(rupiah(cart.total), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                        }
                        Button(
                            onClick = { nav.navigate(Routes.CHECKOUT) },
                            enabled = !vm.busy && cart.groups.all { g -> g.items.all { it.available } },
                        ) { Text(tr("Checkout")) }
                    }
                }
            }
        },
    ) { padding ->
        val inner = Modifier.padding(padding)
        when {
            user == null -> MessageBox(
                Icons.Outlined.LockPerson, tr("Masuk untuk melihat keranjang"), inner,
                body = tr("Simpan buku incaranmu dan checkout kapan saja."),
                actionLabel = tr("Masuk"), onAction = { nav.navigate(Routes.LOGIN) },
            )
            else -> when (val s = state) {
                Load.Loading -> LoadingBox(inner)
                is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = inner)
                is Load.Ready -> if (s.data.count == 0) {
                    MessageBox(
                        Icons.Outlined.ShoppingBag, tr("Keranjangmu masih kosong"), inner,
                        body = tr("Yuk, cari buku favoritmu."),
                        actionLabel = tr("Mulai Belanja"), onAction = { nav.navigateTab(Routes.search()) },
                        animation = "empty",
                    )
                } else {
                    PullToRefreshBox(isRefreshing = false, onRefresh = { vm.load(silent = true) }, modifier = inner) {
                        CartList(s.data, vm, nav)
                    }
                }
            }
        }
    }
}

@Composable
private fun CartList(cart: Cart, vm: CartViewModel, nav: NavHostController) {
    LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        cart.groups.forEach { group ->
            item(key = "store-${group.store.id}") {
                Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                    Row(Modifier.padding(start = 16.dp, top = 12.dp, end = 16.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Outlined.Storefront, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(18.dp))
                        Text(" ${group.store.name}", fontWeight = FontWeight.SemiBold)
                        Text(" · ${group.store.city.orEmpty()}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    group.items.forEachIndexed { i, item ->
                        if (i > 0) HorizontalDivider(Modifier.padding(horizontal = 16.dp))
                        CartRow(item, vm, onOpen = { nav.navigate(Routes.product(item.product.slug)) })
                    }
                }
            }
        }
    }
}

@Composable
private fun CartRow(item: CartItem, vm: CartViewModel, onOpen: () -> Unit) {
    val p = item.product
    Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
        Card(onClick = onOpen) {
            BookCover(p.imageUrl, Modifier.size(width = 60.dp, height = 80.dp))
        }
        Spacer(Modifier.width(12.dp))
        Column(Modifier.weight(1f)) {
            Text(p.title, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Text(rupiah(p.price), color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
            if (!item.available) {
                Text(
                    if (p.stock == 0) tr("Stok habis, hapus untuk lanjut") else tr("Stok tinggal :stock.", "stock" to p.stock),
                    color = MaterialTheme.colorScheme.error,
                    style = MaterialTheme.typography.bodySmall,
                )
            }
            Spacer(Modifier.height(6.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                QuantityStepper(item.quantity, maxOf(1, p.stock), onChange = { vm.setQuantity(item, it) })
                Spacer(Modifier.weight(1f))
                IconButton(onClick = { vm.remove(item) }, enabled = !vm.busy) {
                    Icon(Icons.Outlined.Delete, tr("Hapus"), tint = MaterialTheme.colorScheme.error)
                }
            }
        }
    }
}
