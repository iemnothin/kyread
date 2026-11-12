package id.skyvora.kyread.ui.screens.orders

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.LockPerson
import androidx.compose.material.icons.outlined.Receipt
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.derivedStateOf
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
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Order
import id.skyvora.kyread.data.OrderStatus
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BookCover
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.MessageBox
import id.skyvora.kyread.ui.components.StatusChip
import id.skyvora.kyread.ui.components.formatDate
import id.skyvora.kyread.ui.components.rupiah
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.launch

class OrdersViewModel(private val repo: KyreadRepository) : ViewModel() {
    var status by mutableStateOf<String?>(null)
        private set
    var orders by mutableStateOf<List<Order>>(emptyList())
        private set
    var loading by mutableStateOf(true)
        private set
    var refreshing by mutableStateOf(false)
        private set
    var loadingMore by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    private var page = 1
    private var lastPage = 1

    fun select(value: String?) {
        status = value
        load()
    }

    fun load(pull: Boolean = false) = viewModelScope.launch {
        if (pull) refreshing = true else loading = true
        error = null
        runCatching { repo.orders(status, 1) }
            .onSuccess { orders = it.data; page = it.meta.currentPage; lastPage = it.meta.lastPage }
            .onFailure { error = it.message }
        loading = false
        refreshing = false
    }

    fun loadMore() {
        if (loading || loadingMore || page >= lastPage) return
        viewModelScope.launch {
            loadingMore = true
            runCatching { repo.orders(status, page + 1) }
                .onSuccess { orders = orders + it.data; page = it.meta.currentPage; lastPage = it.meta.lastPage }
            loadingMore = false
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrdersScreen(repo: KyreadRepository, nav: NavHostController, modifier: Modifier = Modifier) {
    val user by repo.user.collectAsStateWithLifecycle()
    val vm = kyViewModel { OrdersViewModel(repo) }
    val list = rememberLazyListState()
    val nearEnd by remember {
        derivedStateOf { (list.layoutInfo.visibleItemsInfo.lastOrNull()?.index ?: 0) >= list.layoutInfo.totalItemsCount - 3 }
    }

    // Muat ulang setiap kali layar dibuka (status pesanan bisa berubah dari layar detail).
    LaunchedEffect(user) { if (user != null) vm.load() }
    LaunchedEffect(nearEnd) { if (nearEnd) vm.loadMore() }

    Scaffold(
        modifier = modifier,
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        topBar = { TopAppBar(title = { Text("Pesanan Saya") }) },
    ) { padding ->
        if (user == null) {
            MessageBox(
                Icons.Outlined.LockPerson, "Masuk untuk melihat pesanan", Modifier.padding(padding),
                actionLabel = "Masuk", onAction = { nav.navigate(Routes.LOGIN) },
            )
            return@Scaffold
        }
        Column(Modifier.padding(padding)) {
            LazyRow(contentPadding = PaddingValues(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                item { FilterChip(vm.status == null, { vm.select(null) }, label = { Text("Semua") }) }
                items(OrderStatus.entries) { s ->
                    FilterChip(vm.status == s.value, { vm.select(s.value) }, label = { Text(s.label) })
                }
            }
            when {
                vm.loading -> LoadingBox()
                vm.error != null -> ErrorBox(vm.error!!, onRetry = { vm.load() })
                vm.orders.isEmpty() -> MessageBox(Icons.Outlined.Receipt, "Belum ada pesanan", body = "Pesanan yang kamu buat akan muncul di sini.")
                else -> PullToRefreshBox(isRefreshing = vm.refreshing, onRefresh = { vm.load(pull = true) }) {
                    LazyColumn(state = list, contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        items(vm.orders, key = { it.code }) { order ->
                            OrderCard(order) { nav.navigate(Routes.order(order.code)) }
                        }
                        if (vm.loadingMore) {
                            item { Box(Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) { CircularProgressIndicator() } }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun OrderCard(order: Order, onClick: () -> Unit) {
    val first = order.items.firstOrNull()
    Card(onClick = onClick, colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(order.store?.name.orEmpty(), fontWeight = FontWeight.SemiBold)
                    Text("${formatDate(order.createdAt)} · ${order.code}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                StatusChip(order.status, order.statusLabel)
            }
            if (first != null) {
                Row(Modifier.padding(top = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                    BookCover(first.imageUrl, Modifier.size(width = 48.dp, height = 64.dp))
                    Spacer(Modifier.width(12.dp))
                    Column(Modifier.weight(1f)) {
                        Text(first.title, maxLines = 1, overflow = TextOverflow.Ellipsis)
                        Text("${first.quantity} × ${rupiah(first.price)}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        if (order.items.size > 1) {
                            Text("+${order.items.size - 1} buku lainnya", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text("Total", style = MaterialTheme.typography.labelSmall)
                        Text(rupiah(order.total), fontWeight = FontWeight.Bold)
                    }
                }
            }
            if (order.can.pay) {
                Text(
                    "Selesaikan pembayaran sebelum batas waktu",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(top = 8.dp),
                )
            }
        }
    }
}
