package id.skyvora.kyread.ui.screens.home

import id.skyvora.kyread.i18n.tr
import id.skyvora.kyread.i18n.trc
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import id.skyvora.kyread.ui.components.IconButton
import id.skyvora.kyread.ui.components.TextButton
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import coil.compose.AsyncImage
import id.skyvora.kyread.R
import id.skyvora.kyread.data.Category
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BookCard
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.Load
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.EnvBadge
import id.skyvora.kyread.ui.components.SectionTitle
import id.skyvora.kyread.ui.kyViewModel
import id.skyvora.kyread.ui.theme.KyBlue
import kotlinx.coroutines.async
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

data class HomeData(
    val banners: List<String>,
    val categories: List<Category>,
    val bestsellers: List<Product>,
    val latest: List<Product>,
    val deals: List<Product> = emptyList(),
)

class HomeViewModel(private val repo: KyreadRepository) : ViewModel() {
    private val _state = MutableStateFlow<Load<HomeData>>(Load.Loading)
    val state = _state.asStateFlow()

    var refreshing by mutableStateOf(false)
        private set

    init {
        load()
    }

    fun load(pull: Boolean = false) = viewModelScope.launch {
        if (pull) refreshing = true else _state.value = Load.Loading
        _state.value = runCatching {
            val meta = async { repo.meta() }
            val categories = async { repo.categories() }
            val best = async { repo.products(sort = "bestseller", perPage = 10) }
            val latest = async { repo.products(sort = "newest", perPage = 10) }
            val deals = async { repo.products(sort = "discount", promo = true, perPage = 10) }
            HomeData(meta.await().banners, categories.await(), best.await().data, latest.await().data, deals.await().data)
        }.fold({ Load.Ready(it) }, { Load.Error(it.message ?: tr("Terjadi kesalahan")) })
        refreshing = false
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(repo: KyreadRepository, nav: NavHostController, modifier: Modifier = Modifier) {
    val vm = kyViewModel { HomeViewModel(repo) }
    val state by vm.state.collectAsStateWithLifecycle()
    val user by repo.user.collectAsStateWithLifecycle()
    val unread by repo.unreadCount.collectAsStateWithLifecycle()

    Scaffold(
        modifier = modifier,
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Image(painterResource(R.drawable.ic_splash), null, modifier = Modifier.size(40.dp))
                        Text("kyread", color = KyBlue, fontWeight = FontWeight.Bold, fontStyle = FontStyle.Italic, fontSize = 26.sp)
                        EnvBadge(Modifier.padding(start = 8.dp))
                    }
                },
                actions = {
                    TextButton(onClick = { nav.navigate(Routes.ASSISTANT) }) {
                        Icon(Icons.Filled.AutoAwesome, null, modifier = Modifier.size(18.dp))
                        Text(" " + tr("Tanya Kyra"))
                    }
                    if (user != null) {
                        IconButton(onClick = { nav.navigate(Routes.NOTIFICATIONS) }) {
                            BadgedBox(badge = { if (unread > 0) Badge { Text(if (unread > 99) "99+" else "$unread") } }) {
                                Icon(Icons.Outlined.Notifications, tr("Notifikasi"))
                            }
                        }
                    }
                },
            )
        },
    ) { padding ->
        when (val s = state) {
            Load.Loading -> LoadingBox(Modifier.padding(padding))
            is Load.Error -> ErrorBox(s.message, onRetry = { vm.load() }, modifier = Modifier.padding(padding))
            is Load.Ready -> PullToRefreshBox(
                isRefreshing = vm.refreshing,
                onRefresh = { vm.load(pull = true) },
                modifier = Modifier.padding(padding),
            ) {
                HomeContent(s.data, nav)
            }
        }
    }
}

@Composable
private fun HomeContent(data: HomeData, nav: NavHostController) {
    val open: (Product) -> Unit = { nav.navigate(Routes.product(it.slug)) }

    LazyVerticalGrid(
        columns = GridCells.Adaptive(160.dp),
        contentPadding = PaddingValues(16.dp),
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            SearchPill(onClick = { nav.navigate(Routes.search()) })
        }
        if (data.banners.isNotEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) { BannerCarousel(data.banners) }
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            Column {
                SectionTitle(tr("Kategori"))
                Spacer(Modifier.height(8.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    items(data.categories, key = { it.id }) { category ->
                        CategoryChip(category) { nav.navigate(Routes.search(category = category.slug)) }
                    }
                }
            }
        }
        if (data.deals.isNotEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                Column {
                    SectionTitle(tr("Sedang Diskon"), Modifier.padding(top = 8.dp), tr("Lihat semua")) { nav.navigate(Routes.search(sort = "discount")) }
                    Spacer(Modifier.height(8.dp))
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        items(data.deals, key = { "deal-${it.id}" }) { BookCard(it, onClick = { open(it) }, modifier = Modifier.width(150.dp)) }
                    }
                }
            }
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            SectionTitle(tr("Terlaris"), Modifier.padding(top = 8.dp), tr("Lihat semua")) { nav.navigate(Routes.search(sort = "bestseller")) }
        }
        items(data.bestsellers, key = { "best-${it.id}" }) { BookCard(it, onClick = { open(it) }) }
        item(span = { GridItemSpan(maxLineSpan) }) {
            SectionTitle(tr("Baru Masuk"), Modifier.padding(top = 8.dp), tr("Lihat semua")) { nav.navigate(Routes.search(sort = "newest")) }
        }
        items(data.latest, key = { "new-${it.id}" }) { BookCard(it, onClick = { open(it) }) }
    }
}

@Composable
private fun SearchPill(onClick: () -> Unit) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(50),
        color = MaterialTheme.colorScheme.surface,
        tonalElevation = 2.dp,
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(Modifier.padding(horizontal = 16.dp, vertical = 12.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Filled.Search, null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.width(8.dp))
            Text(tr("Cari judul, penulis, penerbit…"), color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun BannerCarousel(banners: List<String>) {
    val pager = rememberPagerState { banners.size }
    LaunchedEffect(pager) {
        while (true) {
            delay(4000)
            pager.animateScrollToPage((pager.currentPage + 1) % banners.size)
        }
    }
    Box {
        HorizontalPager(pager, modifier = Modifier.clip(MaterialTheme.shapes.large)) { page ->
            AsyncImage(
                model = banners[page],
                contentDescription = tr("Promo :n", "n" to page + 1),
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxWidth().aspectRatio(2.6f),
            )
        }
        Row(
            Modifier.align(Alignment.BottomCenter).padding(8.dp),
            horizontalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            repeat(banners.size) { i ->
                Box(
                    Modifier
                        .size(if (i == pager.currentPage) 16.dp else 6.dp, 6.dp)
                        .clip(CircleShape)
                        .background(if (i == pager.currentPage) MaterialTheme.colorScheme.surface else MaterialTheme.colorScheme.surface.copy(alpha = 0.5f)),
                )
            }
        }
    }
}

@Composable
private fun CategoryChip(category: Category, onClick: () -> Unit) {
    Column(
        Modifier
            .width(76.dp)
            .clip(MaterialTheme.shapes.medium)
            .clickable(onClick = onClick)
            .padding(vertical = 6.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Surface(shape = CircleShape, color = MaterialTheme.colorScheme.surface, tonalElevation = 1.dp) {
            AsyncImage(category.iconUrl, null, modifier = Modifier.size(56.dp).padding(10.dp))
        }
        Spacer(Modifier.height(4.dp))
        Text(category.name, style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 1)
        category.productsCount?.let {
            Text(trc(":count buku", it), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}
