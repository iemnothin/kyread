package id.skyvora.kyread.ui.screens.checkout

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.ConfirmationNumber
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
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
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.AsyncImage
import id.skyvora.kyread.data.AppMeta
import id.skyvora.kyread.data.Cart
import id.skyvora.kyread.data.CheckoutRequest
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Order
import id.skyvora.kyread.data.VoucherCheck
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.SummaryRow
import id.skyvora.kyread.ui.components.rupiah
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.launch

class CheckoutViewModel(private val repo: KyreadRepository) : ViewModel() {
    var cart by mutableStateOf<Cart?>(null)
        private set
    var meta by mutableStateOf<AppMeta?>(null)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    var submitting by mutableStateOf(false)
        private set

    // Form
    var name by mutableStateOf("")
    var phone by mutableStateOf("")
    var street by mutableStateOf("")
    var city by mutableStateOf("")
    var province by mutableStateOf("")
    var zipcode by mutableStateOf("")
    var notes by mutableStateOf("")
    var saveAddress by mutableStateOf(false)
    var courier by mutableStateOf("jne")
    var payment by mutableStateOf("bca")

    // Voucher
    var voucherInput by mutableStateOf("")
    var voucher by mutableStateOf<VoucherCheck?>(null)
        private set
    var voucherError by mutableStateOf<String?>(null)
        private set
    var checkingVoucher by mutableStateOf(false)
        private set

    val discount: Long get() = voucher?.discount ?: 0

    /** Kode dinormalisasi seperti di server: huruf besar tanpa spasi. */
    private fun normalized(code: String) = code.uppercase().filter { !it.isWhitespace() }

    // Teks tidak diubah saat diketik: mengubahnya di onValueChange mengacaukan komposisi IME (huruf hilang).
    fun onVoucherInput(value: String) {
        voucherInput = value.take(24)
        voucherError = null
        if (voucher != null && voucher?.code != normalized(voucherInput)) voucher = null
    }

    fun applyVoucher() = viewModelScope.launch {
        val code = normalized(voucherInput)
        if (code.isBlank()) return@launch
        checkingVoucher = true
        voucherError = null
        runCatching { repo.checkVoucher(code) }
            .onSuccess { voucher = it; voucherInput = it.code }
            .onFailure { voucher = null; voucherError = it.message }
        checkingVoucher = false
    }

    fun removeVoucher() {
        voucher = null
        voucherInput = ""
        voucherError = null
    }

    fun grandTotal(): Long = (cart?.total ?: 0) + shippingTotal() - discount

    init {
        load()
    }

    fun load() = viewModelScope.launch {
        error = null
        runCatching {
            val c = async { repo.cart() }
            val m = async { repo.meta() }
            cart = c.await()
            meta = m.await()
        }.onFailure { error = it.message }

        repo.user.value?.let { u ->
            name = u.name
            phone = u.phone.orEmpty()
            street = u.address.street.orEmpty()
            city = u.address.city.orEmpty()
            province = u.address.province.orEmpty()
            zipcode = u.address.zipcode.orEmpty()
            saveAddress = !u.address.complete
        }
    }

    fun shippingTotal(): Long {
        val c = meta?.couriers?.firstOrNull { it.code == courier } ?: return 0
        return cart?.groups?.sumOf { c.costFor(it.weight) } ?: 0
    }

    suspend fun submit(): Result<List<Order>> {
        submitting = true
        return runCatching {
            repo.checkout(
                CheckoutRequest(
                    recipientName = name.trim(),
                    recipientPhone = phone.trim(),
                    street = street.trim(),
                    city = city.trim(),
                    province = province.trim(),
                    zipcode = zipcode.trim(),
                    courier = courier,
                    paymentMethod = payment,
                    notes = notes.trim().ifBlank { null },
                    voucherCode = voucher?.code,
                    saveAddress = saveAddress,
                ),
            )
        }.also { submitting = false }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CheckoutScreen(repo: KyreadRepository, nav: NavHostController) {
    val vm = kyViewModel { CheckoutViewModel(repo) }
    val snackbar = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()

    val cart = vm.cart
    val meta = vm.meta

    // Keranjang ternyata kosong: tidak ada yang bisa di-checkout.
    LaunchedEffect(cart) { if (cart != null && cart.count == 0) nav.popBackStack() }

    Scaffold(
        topBar = { TopAppBar(title = { Text("Checkout") }, navigationIcon = { BackIcon { nav.popBackStack() } }) },
        snackbarHost = { SnackbarHost(snackbar) },
        bottomBar = {
            if (cart != null && meta != null) {
                Surface(tonalElevation = 3.dp, shadowElevation = 8.dp) {
                    Row(Modifier.navigationBarsPadding().fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text("Total tagihan", style = MaterialTheme.typography.bodySmall)
                            Text(
                                rupiah(vm.grandTotal()),
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary,
                            )
                        }
                        Button(
                            enabled = !vm.submitting,
                            onClick = {
                                scope.launch {
                                    vm.submit()
                                        .onSuccess { orders ->
                                            val target = if (orders.size == 1) Routes.order(orders.first().code) else Routes.ORDERS
                                            nav.navigate(target) { popUpTo(Routes.CHECKOUT) { inclusive = true } }
                                        }
                                        .onFailure { snackbar.showSnackbar(it.message ?: "Gagal membuat pesanan") }
                                }
                            },
                        ) {
                            if (vm.submitting) CircularProgressIndicator(Modifier.size(18.dp), strokeWidth = 2.dp) else Text("Buat Pesanan")
                        }
                    }
                }
            }
        },
    ) { padding ->
        when {
            vm.error != null -> ErrorBox(vm.error!!, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            cart == null || meta == null -> LoadingBox(Modifier.padding(padding))
            else -> LazyColumn(
                Modifier.padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                item { Section("Alamat Pengiriman") { AddressForm(vm) } }
                item {
                    Section("Ringkasan Pesanan") {
                        cart.groups.forEach { g ->
                            Text(g.store.name, fontWeight = FontWeight.SemiBold)
                            g.items.forEach { item ->
                                SummaryRow("${item.quantity}× ${item.product.title}", rupiah(item.subtotal))
                            }
                            val c = meta.couriers.firstOrNull { it.code == vm.courier }
                            SummaryRow("Ongkir (${"%.1f".format(g.weight / 1000.0)} kg)", rupiah(c?.costFor(g.weight) ?: 0))
                            Spacer(Modifier.height(8.dp))
                        }
                        if (cart.groups.size > 1) {
                            Text(
                                "Pesanan dipisah per toko, masing-masing dengan ongkos kirim sendiri.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }
                item {
                    Section("Kurir") {
                        meta.couriers.forEach { c ->
                            OptionRow(
                                selected = vm.courier == c.code,
                                onSelect = { vm.courier = c.code },
                                logo = c.logoUrl,
                                title = c.label,
                                subtitle = "${rupiah(c.ratePerKg)}/kg · ${c.estimate}",
                            )
                        }
                    }
                }
                item {
                    Section("Metode Pembayaran") {
                        meta.paymentMethods.forEach { p ->
                            OptionRow(selected = vm.payment == p.code, onSelect = { vm.payment = p.code }, logo = p.logoUrl, title = p.label)
                        }
                    }
                }
                item {
                    Section("Catatan untuk penjual") {
                        OutlinedTextField(vm.notes, { vm.notes = it }, Modifier.fillMaxWidth(), placeholder = { Text("Opsional") })
                    }
                }
                item { Section("Voucher Toko") { VoucherField(vm) } }
                item {
                    Section("Rincian Pembayaran") {
                        SummaryRow("Total harga", rupiah(cart.total))
                        SummaryRow("Ongkos kirim", rupiah(vm.shippingTotal()))
                        vm.voucher?.let { SummaryRow("Voucher ${it.code}", "−" + rupiah(it.discount)) }
                        SummaryRow("Total tagihan", rupiah(vm.grandTotal()), bold = true)
                        Text(
                            "Selesaikan pembayaran dalam 24 jam atau pesanan dibatalkan otomatis.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun Section(title: String, content: @Composable () -> Unit) {
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            content()
        }
    }
}

@Composable
private fun VoucherField(vm: CheckoutViewModel) {
    val applied = vm.voucher
    Row(verticalAlignment = Alignment.Top, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        OutlinedTextField(
            value = vm.voucherInput,
            onValueChange = vm::onVoucherInput,
            modifier = Modifier.weight(1f),
            placeholder = { Text("Masukkan kode") },
            leadingIcon = { Icon(Icons.Outlined.ConfirmationNumber, null) },
            singleLine = true,
            isError = vm.voucherError != null,
            supportingText = (vm.voucherError ?: applied?.let { "${it.message} (${it.storeName})" })?.let { msg ->
                { Text(msg, color = if (vm.voucherError != null) MaterialTheme.colorScheme.error else KySuccess) }
            },
            keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Characters, imeAction = ImeAction.Done),
            keyboardActions = KeyboardActions(onDone = { vm.applyVoucher() }),
        )
        if (applied != null) {
            OutlinedButton(onClick = vm::removeVoucher, modifier = Modifier.padding(top = 8.dp)) { Text("Hapus") }
        } else {
            Button(onClick = { vm.applyVoucher() }, enabled = !vm.checkingVoucher && vm.voucherInput.isNotBlank(), modifier = Modifier.padding(top = 8.dp)) {
                if (vm.checkingVoucher) CircularProgressIndicator(Modifier.size(16.dp), strokeWidth = 2.dp) else Text("Pakai")
            }
        }
    }
}

private val KySuccess = androidx.compose.ui.graphics.Color(0xFF16A34A)

@Composable
private fun AddressForm(vm: CheckoutViewModel) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        OutlinedTextField(vm.name, { vm.name = it }, Modifier.fillMaxWidth(), label = { Text("Nama penerima") }, singleLine = true)
        OutlinedTextField(
            vm.phone, { vm.phone = it }, Modifier.fillMaxWidth(),
            label = { Text("No. HP") }, singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
        )
        OutlinedTextField(vm.street, { vm.street = it }, Modifier.fillMaxWidth(), label = { Text("Alamat lengkap") }, minLines = 2)
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedTextField(vm.city, { vm.city = it }, Modifier.weight(1f), label = { Text("Kota") }, singleLine = true)
            OutlinedTextField(vm.province, { vm.province = it }, Modifier.weight(1f), label = { Text("Provinsi") }, singleLine = true)
        }
        OutlinedTextField(
            vm.zipcode, { if (it.length <= 5) vm.zipcode = it.filter(Char::isDigit) }, Modifier.width(160.dp),
            label = { Text("Kode pos") }, singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        )
        Row(verticalAlignment = Alignment.CenterVertically) {
            Checkbox(vm.saveAddress, { vm.saveAddress = it })
            Text("Simpan sebagai alamat utama", style = MaterialTheme.typography.bodyMedium)
        }
    }
}

@Composable
private fun OptionRow(selected: Boolean, onSelect: () -> Unit, logo: String, title: String, subtitle: String? = null) {
    Row(
        Modifier
            .fillMaxWidth()
            .selectable(selected = selected, onClick = onSelect, role = Role.RadioButton)
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        RadioButton(selected = selected, onClick = null)
        Spacer(Modifier.width(8.dp))
        AsyncImage(logo, null, modifier = Modifier.size(width = 56.dp, height = 24.dp))
        Spacer(Modifier.width(12.dp))
        Column(Modifier.weight(1f)) {
            Text(title, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
            subtitle?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
        }
    }
}
