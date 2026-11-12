package id.skyvora.kyread.ui.screens.account

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.outlined.HelpOutline
import androidx.compose.material.icons.automirrored.outlined.Logout
import androidx.compose.material.icons.outlined.AutoAwesome
import androidx.compose.material.icons.outlined.Edit
import androidx.compose.material.icons.outlined.FavoriteBorder
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.Receipt
import androidx.compose.material.icons.outlined.Storefront
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Badge
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.ListItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.navigation.NavHostController
import id.skyvora.kyread.ui.components.UserAvatar
import id.skyvora.kyread.BuildConfig
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.navigateTab
import kotlinx.coroutines.launch

private val webBase = BuildConfig.API_BASE_URL.substringBefore("/api/")

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AccountScreen(repo: KyreadRepository, nav: NavHostController, modifier: Modifier = Modifier) {
    val user by repo.user.collectAsStateWithLifecycle()
    val unread by repo.unreadCount.collectAsStateWithLifecycle()

    // Perbarui jumlah notifikasi & wishlist setiap kali tab Akun dibuka.
    LaunchedEffect(user?.id) { if (user != null) runCatching { repo.refreshUser() } }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var confirmLogout by remember { mutableStateOf(false) }
    val openWeb: (String) -> Unit = { path -> context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(webBase + path))) }

    if (confirmLogout) {
        AlertDialog(
            onDismissRequest = { confirmLogout = false },
            text = { Text("Keluar dari akunmu?") },
            confirmButton = { TextButton(onClick = { confirmLogout = false; scope.launch { repo.logout() } }) { Text("Keluar") } },
            dismissButton = { TextButton(onClick = { confirmLogout = false }) { Text("Batal") } },
        )
    }

    Scaffold(
        modifier = modifier,
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        topBar = { TopAppBar(title = { Text("Akun") }) },
    ) { padding ->
        Column(Modifier.padding(padding).verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            val u = user
            Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                if (u == null) {
                    Column(Modifier.fillMaxWidth().padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Selamat datang di kyread", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Text(
                            "Masuk untuk belanja, melacak pesanan, dan memberi ulasan.",
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            style = MaterialTheme.typography.bodyMedium,
                        )
                        Spacer(Modifier.height(16.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(onClick = { nav.navigate(Routes.REGISTER) }) { Text("Daftar") }
                            Button(onClick = { nav.navigate(Routes.LOGIN) }) { Text("Masuk") }
                        }
                    }
                } else {
                    Row(Modifier.padding(20.dp), verticalAlignment = Alignment.CenterVertically) {
                        UserAvatar(u.displayName, u.avatarUrl)
                        Spacer(Modifier.width(16.dp))
                        Column(Modifier.weight(1f)) {
                            Text(u.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                            Text("@${u.username}", color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Text(u.email, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }
            }

            Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                if (u != null) {
                    MenuItem(Icons.Outlined.Receipt, "Pesanan Saya") { nav.navigateTab(Routes.ORDERS) }
                    HorizontalDivider()
                    MenuItem(Icons.Outlined.FavoriteBorder, "Wishlist", count = u.wishlistCount, countIsBadge = false) { nav.navigate(Routes.WISHLIST) }
                    HorizontalDivider()
                    MenuItem(Icons.Outlined.Notifications, "Notifikasi", count = unread) { nav.navigate(Routes.NOTIFICATIONS) }
                    HorizontalDivider()
                    MenuItem(Icons.Outlined.Edit, "Edit Profil & Alamat") { nav.navigate(Routes.EDIT_PROFILE) }
                    HorizontalDivider()
                }
                MenuItem(Icons.Outlined.AutoAwesome, "Tanya Kyra (asisten AI)") { nav.navigate(Routes.ASSISTANT) }
                HorizontalDivider()
                MenuItem(
                    Icons.Outlined.Storefront,
                    if (u?.hasStore == true) "Kelola toko di kyreadSeller" else "Mulai jualan di kyreadSeller",
                    supporting = "Dibuka di browser",
                ) { openWeb("/seller") }
                HorizontalDivider()
                MenuItem(Icons.AutoMirrored.Outlined.HelpOutline, "Pusat Bantuan") { openWeb("/help") }
                if (u != null) {
                    HorizontalDivider()
                    MenuItem(Icons.AutoMirrored.Outlined.Logout, "Keluar", danger = true) { confirmLogout = true }
                }
            }

            Text(
                "kyread v${BuildConfig.VERSION_NAME}" + if (BuildConfig.IS_STAGING) " · staging (${BuildConfig.API_BASE_URL.substringAfter("://").substringBefore("/")})" else "",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.align(Alignment.CenterHorizontally),
            )
        }
    }
}

@Composable
private fun MenuItem(
    icon: ImageVector,
    title: String,
    supporting: String? = null,
    danger: Boolean = false,
    count: Int = 0,
    countIsBadge: Boolean = true,
    onClick: () -> Unit,
) {
    val color = if (danger) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.onSurface
    ListItem(
        headlineContent = { Text(title, color = color) },
        supportingContent = supporting?.let { { Text(it) } },
        leadingContent = { Icon(icon, null, tint = if (danger) color else MaterialTheme.colorScheme.primary) },
        trailingContent = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                if (count > 0) {
                    if (countIsBadge) Badge { Text(if (count > 99) "99+" else "$count") }
                    else Text("$count", color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(Modifier.width(4.dp))
                }
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, null)
            }
        },
        modifier = Modifier.clickable(onClick = onClick),
    )
}
