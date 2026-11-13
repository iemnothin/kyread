package id.skyvora.kyread.ui.screens.orders

import id.skyvora.kyread.i18n.tr
import android.content.ClipData
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.ContentCopy
import androidx.compose.material.icons.outlined.Description
import androidx.compose.material3.AlertDialog
import id.skyvora.kyread.ui.components.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import id.skyvora.kyread.ui.components.IconButton
import androidx.compose.material3.MaterialTheme
import id.skyvora.kyread.ui.components.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import id.skyvora.kyread.ui.components.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.ClipEntry
import androidx.compose.ui.platform.LocalClipboard
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Order
import id.skyvora.kyread.data.OrderItem
import id.skyvora.kyread.data.OrderStatus
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.BookCover
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.RatingStars
import id.skyvora.kyread.ui.components.StatusChip
import id.skyvora.kyread.ui.components.SuccessDialog
import id.skyvora.kyread.ui.components.SummaryRow
import id.skyvora.kyread.ui.components.formatDateTime
import id.skyvora.kyread.ui.components.rupiah
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.launch

class OrderDetailViewModel(private val repo: KyreadRepository, private val code: String) : ViewModel() {
    var state by mutableStateOf<Load<Order>>(Load.Loading)
        private set
    var busy by mutableStateOf(false)
        private set

    init {
        load()
    }

    fun load() = viewModelScope.launch {
        state = Load.Loading
        state = runCatching { repo.order(code) }.fold({ Load.Ready(it) }, { Load.Error(it.message ?: tr("Terjadi kesalahan")) })
    }

    /** Jalankan aksi pesanan; kembalikan pesan untuk snackbar. */
    suspend fun act(success: String, block: suspend () -> Order): String {
        busy = true
        val message = runCatching { block() }
            .fold({ state = Load.Ready(it); success }, { it.message ?: tr("Gagal") })
        busy = false
        return message
    }

    suspend fun pay() = act(tr("Pembayaran dikonfirmasi. Penjual akan segera memproses pesananmu.")) { repo.payOrder(code) }
    suspend fun cancel() = act(tr("Pesanan dibatalkan.")) { repo.cancelOrder(code) }
    suspend fun complete() = act(tr("Pesanan selesai. Jangan lupa beri ulasan ya!")) { repo.completeOrder(code) }
    suspend fun review(productId: Int, rating: Int, comment: String) =
        act(tr("Ulasan tersimpan. Terima kasih!")) { repo.reviewOrder(code, productId, rating, comment.ifBlank { null }) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrderDetailScreen(repo: KyreadRepository, nav: NavHostController, code: String, celebrate: Boolean = false) {
    val vm = kyViewModel(key = "order-$code") { OrderDetailViewModel(repo, code) }
    val snackbar = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    var confirm by remember { mutableStateOf<Pair<String, suspend () -> String>?>(null) }
    // Dialog animasi sukses: setelah checkout (celebrate) dan setelah pembayaran berhasil.
    var celebration by rememberSaveable { mutableStateOf(if (celebrate) tr("Pesanan berhasil dibuat. Silakan selesaikan pembayaran.") else null) }
    celebration?.let { SuccessDialog(it) { celebration = null } }

    fun run(action: suspend () -> String) = scope.launch { snackbar.showSnackbar(action()) }

    confirm?.let { (text, action) ->
        AlertDialog(
            onDismissRequest = { confirm = null },
            text = { Text(text) },
            confirmButton = { TextButton(onClick = { confirm = null; run(action) }) { Text(tr("Ya")) } },
            dismissButton = { TextButton(onClick = { confirm = null }) { Text(tr("Batal")) } },
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(code) },
                navigationIcon = { BackIcon { nav.popBackStack() } },
                actions = {
                    // Invoice dibuka di browser lewat tautan bertanda tangan (berlaku 1 jam).
                    (vm.state as? Load.Ready)?.data?.invoiceUrl?.let { url ->
                        IconButton(onClick = { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))) }) {
                            Icon(Icons.Outlined.Description, tr("Invoice"))
                        }
                    }
                },
            )
        },
        snackbarHost = { SnackbarHost(snackbar) },
    ) { padding ->
        when (val s = vm.state) {
            Load.Loading -> LoadingBox(Modifier.padding(padding))
            is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            is Load.Ready -> {
                val order = s.data
                LazyColumn(
                    Modifier.padding(padding),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    item { StatusCard(order) }

                    if (order.can.pay) {
                        item {
                            PaymentCard(
                                order = order,
                                busy = vm.busy,
                                onPay = {
                                    scope.launch {
                                        val message = vm.pay()
                                        val paid = (vm.state as? Load.Ready)?.data?.status == OrderStatus.Paid.value
                                        if (paid) celebration = message else snackbar.showSnackbar(message)
                                    }
                                },
                                onCancel = { confirm = tr("Batalkan pesanan ini?") to { vm.cancel() } },
                            )
                        }
                    }

                    if (order.status == OrderStatus.Shipped.value) {
                        item {
                            Block(tr("Pengiriman")) {
                                Text(tr("Nomor resi :courier", "courier" to order.courier.label), style = MaterialTheme.typography.bodySmall)
                                Text(order.trackingNumber.orEmpty(), fontFamily = FontFamily.Monospace, fontWeight = FontWeight.Bold)
                                Spacer(Modifier.height(8.dp))
                                Button(
                                    onClick = { confirm = tr("Pastikan buku sudah kamu terima. Selesaikan pesanan?") to { vm.complete() } },
                                    enabled = !vm.busy,
                                    modifier = Modifier.fillMaxWidth(),
                                ) { Text(tr("Pesanan Diterima")) }
                            }
                        }
                    }

                    item {
                        Block(order.store?.name ?: tr("Buku")) {
                            order.items.forEachIndexed { i, item ->
                                if (i > 0) HorizontalDivider(Modifier.padding(vertical = 8.dp))
                                ItemRow(
                                    item = item,
                                    order = order,
                                    busy = vm.busy,
                                    onOpen = { item.productSlug?.let { nav.navigate(Routes.product(it)) } },
                                    onReview = { rating, comment -> run { vm.review(item.productId!!, rating, comment) } },
                                )
                            }
                        }
                    }

                    item {
                        Block(tr("Alamat Pengiriman")) {
                            Text("${order.recipient.name} · ${order.recipient.phone}", fontWeight = FontWeight.SemiBold)
                            Text(order.recipient.address, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Text(tr("Kurir: :courier", "courier" to order.courier.label), style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 4.dp))
                            order.notes?.let { Text(tr("Catatan: :notes", "notes" to it), style = MaterialTheme.typography.bodySmall) }
                        }
                    }

                    item {
                        Block(tr("Rincian Pembayaran")) {
                            SummaryRow(tr("Metode"), order.paymentMethod.label)
                            SummaryRow(tr("Subtotal"), rupiah(order.subtotal))
                            SummaryRow(tr("Ongkos kirim"), rupiah(order.shippingCost))
                            if (order.discount > 0) {
                                SummaryRow(tr("Voucher :code", "code" to order.voucherCode.orEmpty()), "−" + rupiah(order.discount), valueColor = Color(0xFF16A34A))
                            }
                            HorizontalDivider(Modifier.padding(vertical = 6.dp))
                            SummaryRow(tr("Total"), rupiah(order.total), bold = true, valueColor = MaterialTheme.colorScheme.primary)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun Block(title: String, content: @Composable () -> Unit) {
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            content()
        }
    }
}

@Composable
private fun StatusCard(order: Order) {
    Block(tr("Status Pesanan")) {
        StatusChip(order.status, order.statusLabel)
        Spacer(Modifier.height(8.dp))
        listOf(
            tr("Dibuat") to order.createdAt,
            tr("Dibayar") to order.paidAt,
            tr("Dikirim") to order.shippedAt,
            tr("Selesai") to order.completedAt,
            tr("Dibatalkan") to order.cancelledAt,
        ).filter { it.second != null }.forEach { (label, time) ->
            SummaryRow(label, formatDateTime(time))
        }
    }
}

@Composable
private fun PaymentCard(order: Order, busy: Boolean, onPay: () -> Unit, onCancel: () -> Unit) {
    val clipboard = LocalClipboard.current
    val scope = rememberCoroutineScope()
    Block(tr("Selesaikan Pembayaran")) {
        Text(tr("Bayar sebelum :date via :method.", "date" to formatDateTime(order.paymentDeadline), "method" to order.paymentMethod.label))
        Spacer(Modifier.height(8.dp))
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(tr("Nomor pembayaran"), style = MaterialTheme.typography.bodySmall)
                Text(order.virtualAccount.orEmpty(), fontFamily = FontFamily.Monospace, fontWeight = FontWeight.Bold)
            }
            IconButton(onClick = {
                scope.launch { clipboard.setClipEntry(ClipEntry(ClipData.newPlainText("VA", order.virtualAccount.orEmpty()))) }
            }) { Icon(Icons.Outlined.ContentCopy, tr("Salin")) }
        }
        SummaryRow(tr("Jumlah"), rupiah(order.total), bold = true)
        Spacer(Modifier.height(12.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedButton(onClick = onCancel, enabled = !busy, modifier = Modifier.weight(1f)) { Text(tr("Batalkan")) }
            Button(onClick = onPay, enabled = !busy, modifier = Modifier.weight(1f)) { Text(tr("Saya Sudah Bayar")) }
        }
        Text(
            tr("Mode demo: pembayaran disimulasikan."),
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(top = 8.dp),
        )
    }
}

@Composable
private fun ItemRow(item: OrderItem, order: Order, busy: Boolean, onOpen: () -> Unit, onReview: (Int, String) -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Card(onClick = onOpen, enabled = item.productSlug != null) {
            BookCover(item.imageUrl, Modifier.size(width = 52.dp, height = 70.dp))
        }
        Spacer(Modifier.width(12.dp))
        Column(Modifier.weight(1f)) {
            Text(item.title, fontWeight = FontWeight.Medium)
            Text("${item.quantity} × ${rupiah(item.price)}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        Text(rupiah(item.subtotal), fontWeight = FontWeight.SemiBold)
    }

    if (order.can.review && item.productId != null) {
        val existing = order.reviews.firstOrNull { it.productId == item.productId }
        var rating by remember(existing) { mutableStateOf(existing?.rating ?: 5) }
        var comment by remember(existing) { mutableStateOf(existing?.comment.orEmpty()) }
        Column(Modifier.padding(top = 8.dp)) {
            Text(if (existing != null) tr("Ulasanmu") else tr("Beri ulasan"), style = MaterialTheme.typography.labelLarge)
            RatingStars(rating, size = 28, onChange = { rating = it })
            OutlinedTextField(
                comment, { comment = it },
                modifier = Modifier.fillMaxWidth(),
                placeholder = { Text(tr("Bagaimana bukunya?")) },
                minLines = 2,
            )
            TextButton(onClick = { onReview(rating, comment) }, enabled = !busy, modifier = Modifier.align(Alignment.End)) {
                Text(if (existing != null) tr("Perbarui Ulasan") else tr("Kirim Ulasan"))
            }
        }
    }
}
