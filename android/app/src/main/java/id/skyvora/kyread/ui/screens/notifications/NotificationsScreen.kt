package id.skyvora.kyread.ui.screens.notifications

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.NotificationsNone
import androidx.compose.material.icons.outlined.ShoppingBag
import androidx.compose.material.icons.outlined.Storefront
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.ListItem
import androidx.compose.material3.ListItemDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.BuildConfig
import id.skyvora.kyread.data.AppNotification
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.MessageBox
import id.skyvora.kyread.ui.components.formatDateTime
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.launch

class NotificationsViewModel(private val repo: KyreadRepository) : ViewModel() {
    var state by mutableStateOf<Load<List<AppNotification>>>(Load.Loading)
        private set
    var refreshing by mutableStateOf(false)
        private set

    init {
        load()
    }

    /** Muat daftar (status "baru" tetap terlihat di sesi ini), lalu tandai semuanya dibaca. */
    fun load(pull: Boolean = false) = viewModelScope.launch {
        if (pull) refreshing = true else state = Load.Loading
        state = runCatching { repo.notifications().data }
            .fold({ Load.Ready(it) }, { Load.Error(it.message ?: "Terjadi kesalahan") })
        refreshing = false
        if ((state as? Load.Ready)?.data?.any { !it.read } == true) runCatching { repo.markNotificationsRead() }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationsScreen(repo: KyreadRepository, nav: NavHostController) {
    val vm = kyViewModel { NotificationsViewModel(repo) }
    val context = LocalContext.current
    val webBase = BuildConfig.API_BASE_URL.substringBefore("/api/")

    fun open(n: AppNotification) {
        val code = n.orderCode ?: return
        if (n.audience == "seller") {
            // Pesanan toko dikelola di kyreadSeller (web).
            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("$webBase/seller/orders/$code")))
        } else {
            nav.navigate(Routes.order(code))
        }
    }

    Scaffold(
        topBar = { TopAppBar(title = { Text("Notifikasi") }, navigationIcon = { BackIcon { nav.popBackStack() } }) },
    ) { padding ->
        when (val s = vm.state) {
            Load.Loading -> LoadingBox(Modifier.padding(padding))
            is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            is Load.Ready -> PullToRefreshBox(vm.refreshing, onRefresh = { vm.load(pull = true) }, modifier = Modifier.padding(padding)) {
                if (s.data.isEmpty()) {
                    MessageBox(
                        Icons.Outlined.NotificationsNone,
                        "Belum ada notifikasi",
                        body = "Kabar tentang pesananmu akan muncul di sini.",
                    )
                } else {
                    LazyColumn {
                        items(s.data, key = { it.id }) { n ->
                            NotificationRow(n) { open(n) }
                            HorizontalDivider()
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun NotificationRow(n: AppNotification, onClick: () -> Unit) {
    val seller = n.audience == "seller"
    ListItem(
        modifier = Modifier.clickable(onClick = onClick),
        colors = ListItemDefaults.colors(
            containerColor = if (n.read) MaterialTheme.colorScheme.surface else MaterialTheme.colorScheme.secondaryContainer,
        ),
        leadingContent = {
            Icon(if (seller) Icons.Outlined.Storefront else Icons.Outlined.ShoppingBag, null, tint = MaterialTheme.colorScheme.primary)
        },
        overlineContent = if (seller) ({ Text("kyreadSeller") }) else null,
        headlineContent = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(n.title, fontWeight = if (n.read) FontWeight.Normal else FontWeight.Bold)
                if (!n.read) {
                    Box(Modifier.padding(start = 6.dp).size(8.dp).background(MaterialTheme.colorScheme.primary, CircleShape))
                }
            }
        },
        supportingContent = {
            Text("${n.body}\n${formatDateTime(n.createdAt)}", style = MaterialTheme.typography.bodySmall)
        },
    )
}
