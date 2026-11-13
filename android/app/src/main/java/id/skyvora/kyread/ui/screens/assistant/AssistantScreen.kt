package id.skyvora.kyread.ui.screens.assistant

import id.skyvora.kyread.i18n.tr
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.outlined.RestartAlt
import androidx.compose.material3.AssistChip
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilledIconButton
import androidx.compose.material3.Icon
import id.skyvora.kyread.ui.components.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.ChatMessage
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.BookCard
import id.skyvora.kyread.ui.kyViewModel
import id.skyvora.kyread.ui.theme.KyBlue
import id.skyvora.kyread.ui.theme.KyBlueDark
import kotlinx.coroutines.launch

data class ChatEntry(val role: String, val text: String, val products: List<Product> = emptyList(), val error: Boolean = false)

private val Starters get() = listOf(
    tr("Rekomendasi novel misteri yang seru"),
    tr("Buku hadiah di bawah Rp 100.000"),
    tr("Novel Tere Liye apa saja yang ada?"),
    tr("Bagaimana cara membayar pesanan?"),
)

class AssistantViewModel(private val repo: KyreadRepository) : ViewModel() {
    val entries = mutableStateListOf<ChatEntry>()
    var input by mutableStateOf("")
    var thinking by mutableStateOf(false)
        private set

    fun send(text: String = input) {
        val content = text.trim()
        if (content.isEmpty() || thinking) return
        entries.removeAll { it.error }
        entries += ChatEntry("user", content)
        input = ""
        thinking = true
        viewModelScope.launch {
            val history = entries.filter { !it.error }.map { ChatMessage(it.role, it.text) }
            runCatching { repo.askAssistant(history) }
                .onSuccess { entries += ChatEntry("assistant", it.reply, it.products) }
                .onFailure { entries += ChatEntry("assistant", it.message ?: tr("Kyra gagal menjawab."), error = true) }
            thinking = false
        }
    }

    fun reset() {
        entries.clear()
        input = ""
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AssistantScreen(repo: KyreadRepository, nav: NavHostController) {
    val vm = kyViewModel { AssistantViewModel(repo) }
    val list = rememberLazyListState()

    LaunchedEffect(vm.entries.size, vm.thinking) {
        val count = vm.entries.size + if (vm.thinking) 1 else 0
        if (count > 0) list.animateScrollToItem(count - 1)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Avatar()
                        Column(Modifier.padding(start = 10.dp)) {
                            Text(tr("Tanya Kyra"), fontWeight = FontWeight.Bold)
                            Text(tr("Asisten buku AI"), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                },
                navigationIcon = { BackIcon { nav.popBackStack() } },
                actions = {
                    if (vm.entries.isNotEmpty()) IconButton(onClick = vm::reset) { Icon(Icons.Outlined.RestartAlt, tr("Mulai ulang")) }
                },
            )
        },
        bottomBar = {
            Surface(tonalElevation = 3.dp) {
                Row(
                    Modifier.navigationBarsPadding().imePadding().padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    OutlinedTextField(
                        value = vm.input,
                        onValueChange = { if (it.length <= 2000) vm.input = it },
                        modifier = Modifier.weight(1f),
                        placeholder = { Text(tr("Tulis pertanyaanmu…")) },
                        maxLines = 4,
                        shape = RoundedCornerShape(24.dp),
                    )
                    Spacer(Modifier.width(8.dp))
                    FilledIconButton(onClick = { vm.send() }, enabled = vm.input.isNotBlank() && !vm.thinking) {
                        Icon(Icons.AutoMirrored.Filled.Send, tr("Kirim"))
                    }
                }
            }
        },
    ) { padding ->
        LazyColumn(
            state = list,
            modifier = Modifier.padding(padding),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            if (vm.entries.isEmpty()) {
                item { Intro(onPick = vm::send) }
            }
            itemsIndexed(vm.entries) { _, entry ->
                if (entry.role == "user") UserBubble(entry.text) else BotBubble(entry, nav)
            }
            if (vm.thinking) {
                item { BotBubble(ChatEntry("assistant", tr("Kyra sedang mencari…")), nav, muted = true) }
            }
        }
    }
}

@Composable
private fun Avatar(size: Int = 36) {
    Box(
        Modifier.size(size.dp).clip(CircleShape).background(Brush.linearGradient(listOf(KyBlue, KyBlueDark))),
        contentAlignment = Alignment.Center,
    ) { Icon(Icons.Filled.AutoAwesome, null, tint = Color.White, modifier = Modifier.size((size / 2).dp)) }
}

@OptIn(androidx.compose.foundation.layout.ExperimentalLayoutApi::class)
@Composable
private fun Intro(onPick: (String) -> Unit) {
    Column(Modifier.fillMaxWidth().padding(vertical = 24.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        Avatar(64)
        Text(
            tr("Halo! Aku Kyra. Ceritakan buku seperti apa yang kamu cari, nanti aku carikan dari katalog kyread."),
            modifier = Modifier.padding(16.dp),
            style = MaterialTheme.typography.bodyLarge,
        )
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterHorizontally)) {
            Starters.forEach { s -> AssistChip(onClick = { onPick(s) }, label = { Text(s) }) }
        }
        Text(
            tr("Kyra bisa keliru. Cek kembali detail buku sebelum membeli."),
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(top = 16.dp),
        )
    }
}

@Composable
private fun UserBubble(text: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
        Surface(
            color = MaterialTheme.colorScheme.primary,
            shape = RoundedCornerShape(18.dp, 18.dp, 4.dp, 18.dp),
            modifier = Modifier.widthIn(max = 300.dp),
        ) {
            Text(text, color = MaterialTheme.colorScheme.onPrimary, modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp))
        }
    }
}

@Composable
private fun BotBubble(entry: ChatEntry, nav: NavHostController, muted: Boolean = false) {
    Row(verticalAlignment = Alignment.Top) {
        Avatar(30)
        Spacer(Modifier.width(8.dp))
        Column(Modifier.weight(1f)) {
            Surface(
                color = if (entry.error) MaterialTheme.colorScheme.errorContainer else MaterialTheme.colorScheme.surfaceVariant,
                shape = RoundedCornerShape(18.dp, 18.dp, 18.dp, 4.dp),
            ) {
                Text(
                    entry.text,
                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
                    color = when {
                        entry.error -> MaterialTheme.colorScheme.onErrorContainer
                        muted -> MaterialTheme.colorScheme.onSurfaceVariant
                        else -> MaterialTheme.colorScheme.onSurface
                    },
                )
            }
            if (entry.products.isNotEmpty()) {
                LazyRow(Modifier.padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    items(entry.products, key = { it.id }) { p ->
                        BookCard(p, onClick = { nav.navigate(Routes.product(p.slug)) }, modifier = Modifier.width(150.dp))
                    }
                }
            }
        }
    }
}
