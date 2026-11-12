package id.skyvora.kyread.ui.screens.wishlist

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.FavoriteBorder
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.components.BookCard
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.MessageBox
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.launch

class WishlistViewModel(private val repo: KyreadRepository) : ViewModel() {
    var state by mutableStateOf<Load<List<Product>>>(Load.Loading)
        private set

    /** Dipanggil setiap layar tampil, agar perubahan dari halaman detail ikut terlihat. */
    fun load() = viewModelScope.launch {
        if (state !is Load.Ready) state = Load.Loading
        state = runCatching { repo.wishlist().data }
            .fold({ Load.Ready(it) }, { Load.Error(it.message ?: "Terjadi kesalahan") })
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun WishlistScreen(repo: KyreadRepository, nav: NavHostController) {
    val vm = kyViewModel { WishlistViewModel(repo) }
    LaunchedEffect(Unit) { vm.load() }

    Scaffold(
        topBar = { TopAppBar(title = { Text("Wishlist") }, navigationIcon = { BackIcon { nav.popBackStack() } }) },
    ) { padding ->
        when (val s = vm.state) {
            Load.Loading -> LoadingBox(Modifier.padding(padding))
            is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            is Load.Ready -> if (s.data.isEmpty()) {
                MessageBox(
                    Icons.Outlined.FavoriteBorder,
                    "Wishlist masih kosong",
                    Modifier.padding(padding),
                    body = "Tekan ikon hati di halaman buku untuk menyimpannya di sini.",
                    actionLabel = "Jelajahi Katalog",
                    onAction = { nav.navigate(Routes.search()) },
                )
            } else {
                LazyVerticalGrid(
                    columns = GridCells.Adaptive(160.dp),
                    modifier = Modifier.padding(padding),
                    contentPadding = PaddingValues(16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    items(s.data, key = { it.id }) { p ->
                        BookCard(p, onClick = { nav.navigate(Routes.product(p.slug)) })
                    }
                }
            }
        }
    }
}
