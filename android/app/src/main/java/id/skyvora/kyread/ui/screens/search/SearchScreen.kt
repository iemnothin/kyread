package id.skyvora.kyread.ui.screens.search

import android.graphics.Bitmap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.itemsIndexed
import androidx.compose.foundation.lazy.grid.rememberLazyGridState
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Sort
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.outlined.AutoAwesome
import androidx.compose.material.icons.outlined.Book
import androidx.compose.material.icons.outlined.Category
import androidx.compose.material.icons.outlined.PersonOutline
import androidx.compose.material.icons.outlined.PhotoCamera
import androidx.compose.material.icons.outlined.PhotoLibrary
import androidx.compose.material.icons.outlined.SearchOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.derivedStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.Category
import id.skyvora.kyread.data.IdentifiedBook
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.Product
import id.skyvora.kyread.data.Suggestion
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BookCard
import id.skyvora.kyread.ui.components.ErrorBox
import id.skyvora.kyread.ui.components.LoadingBox
import id.skyvora.kyread.ui.components.MessageBox
import id.skyvora.kyread.ui.components.readImageForUpload
import id.skyvora.kyread.ui.components.toUploadJpeg
import id.skyvora.kyread.ui.kyViewModel
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

private val SortOptions = listOf(
    "newest" to "Terbaru",
    "bestseller" to "Terlaris",
    "price_asc" to "Harga terendah",
    "price_desc" to "Harga tertinggi",
    "title" to "Judul A-Z",
)

class SearchViewModel(
    private val repo: KyreadRepository,
    initialCategory: String?,
    initialQuery: String?,
    initialSort: String?,
) : ViewModel() {
    var query by mutableStateOf(initialQuery.orEmpty())
        private set
    var category by mutableStateOf(initialCategory)
        private set
    var sort by mutableStateOf(initialSort ?: "newest")
        private set

    var categories by mutableStateOf<List<Category>>(emptyList())
        private set
    var products by mutableStateOf<List<Product>>(emptyList())
        private set
    var total by mutableStateOf(0)
        private set
    var loading by mutableStateOf(true)
        private set
    var loadingMore by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    /** Saran kata untuk teks yang sedang diketik. */
    var suggestions by mutableStateOf<List<Suggestion>>(emptyList())
        private set

    /** Hasil pengenalan foto (null = pencarian biasa). */
    var identified by mutableStateOf<IdentifiedBook?>(null)
        private set

    private var page = 1
    private var lastPage = 1
    private var job: Job? = null
    private var suggestJob: Job? = null

    init {
        viewModelScope.launch { categories = runCatching { repo.categories() }.getOrDefault(emptyList()) }
        search()
    }

    /** Dipanggil setiap huruf diketik: ambil saran setelah jeda singkat (debounce 150 ms). */
    fun onQueryChange(text: String) {
        query = text
        suggestJob?.cancel()
        if (text.isBlank()) {
            suggestions = emptyList()
            return
        }
        suggestJob = viewModelScope.launch {
            delay(150)
            suggestions = runCatching { repo.suggest(text.trim()) }.getOrDefault(suggestions)
        }
    }

    fun clearSuggestions() {
        suggestJob?.cancel()
        suggestions = emptyList()
    }

    fun selectCategory(slug: String?) {
        category = slug
        search()
    }

    fun selectSort(value: String) {
        sort = value
        search()
    }

    fun applySuggestion(s: Suggestion) {
        clearSuggestions()
        if (s.type == "category" && s.slug != null) {
            query = ""
            selectCategory(s.slug)
        } else {
            query = s.value
            search()
        }
    }

    fun search() {
        clearSuggestions()
        identified = null
        job?.cancel()
        job = viewModelScope.launch {
            loading = true
            error = null
            runCatching { repo.products(q = query.trim(), category = category, sort = sort, page = 1) }
                .onSuccess {
                    products = it.data
                    total = it.meta.total
                    page = it.meta.currentPage
                    lastPage = it.meta.lastPage
                }
                .onFailure { error = it.message }
            loading = false
        }
    }

    /** Kirim foto ke server; AI mengenali buku lalu hasilnya dicocokkan dengan katalog. */
    fun searchByImage(readBytes: suspend () -> ByteArray) {
        clearSuggestions()
        job?.cancel()
        job = viewModelScope.launch {
            loading = true
            error = null
            runCatching {
                val bytes = withContext(Dispatchers.Default) { readBytes() }
                repo.searchByImage(bytes, "image/jpeg")
            }.onSuccess {
                identified = it.identified
                query = it.query
                category = null
                products = it.data
                total = it.data.size
                page = 1
                lastPage = 1
            }.onFailure { error = it.message }
            loading = false
        }
    }

    fun loadMore() {
        if (loading || loadingMore || page >= lastPage) return
        viewModelScope.launch {
            loadingMore = true
            runCatching { repo.products(q = query.trim(), category = category, sort = sort, page = page + 1) }
                .onSuccess {
                    products = products + it.data
                    page = it.meta.currentPage
                    lastPage = it.meta.lastPage
                }
            loadingMore = false
        }
    }
}

@Composable
fun SearchScreen(
    repo: KyreadRepository,
    nav: NavHostController,
    modifier: Modifier = Modifier,
    initialCategory: String? = null,
    initialQuery: String? = null,
    initialSort: String? = null,
) {
    val vm = kyViewModel(key = "search-$initialCategory-$initialQuery-$initialSort") {
        SearchViewModel(repo, initialCategory, initialQuery, initialSort)
    }
    val context = LocalContext.current
    val focus = LocalFocusManager.current
    var focused by remember { mutableStateOf(false) }
    var imageMenu by remember { mutableStateOf(false) }

    val takePhoto = rememberLauncherForActivityResult(ActivityResultContracts.TakePicturePreview()) { bitmap: Bitmap? ->
        if (bitmap != null) vm.searchByImage { bitmap.toUploadJpeg() }
    }
    val pickPhoto = rememberLauncherForActivityResult(ActivityResultContracts.PickVisualMedia()) { uri ->
        if (uri != null) vm.searchByImage { context.readImageForUpload(uri) }
    }

    val grid = rememberLazyGridState()
    val nearEnd by remember {
        derivedStateOf {
            val last = grid.layoutInfo.visibleItemsInfo.lastOrNull()?.index ?: 0
            last >= grid.layoutInfo.totalItemsCount - 4
        }
    }
    LaunchedEffect(nearEnd) { if (nearEnd) vm.loadMore() }

    Scaffold(modifier = modifier, contentWindowInsets = WindowInsets(0, 0, 0, 0)) { padding ->
        Column(Modifier.padding(padding).statusBarsPadding()) {
            OutlinedTextField(
                value = vm.query,
                onValueChange = vm::onQueryChange,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
                    .onFocusChanged { focused = it.isFocused },
                placeholder = { Text("Cari judul, penulis, penerbit…") },
                leadingIcon = { Icon(Icons.Filled.Search, null) },
                trailingIcon = {
                    Row {
                        if (vm.query.isNotEmpty()) {
                            IconButton(onClick = { vm.onQueryChange(""); vm.search() }) { Icon(Icons.Filled.Close, "Hapus") }
                        }
                        Box {
                            IconButton(onClick = { imageMenu = true }) { Icon(Icons.Outlined.PhotoCamera, "Cari dengan gambar") }
                            DropdownMenu(expanded = imageMenu, onDismissRequest = { imageMenu = false }) {
                                DropdownMenuItem(
                                    text = { Text("Foto sampul buku") },
                                    leadingIcon = { Icon(Icons.Outlined.PhotoCamera, null) },
                                    onClick = { imageMenu = false; takePhoto.launch(null) },
                                )
                                DropdownMenuItem(
                                    text = { Text("Pilih dari galeri") },
                                    leadingIcon = { Icon(Icons.Outlined.PhotoLibrary, null) },
                                    onClick = {
                                        imageMenu = false
                                        pickPhoto.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly))
                                    },
                                )
                            }
                        }
                    }
                },
                singleLine = true,
                shape = MaterialTheme.shapes.large,
                keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                keyboardActions = KeyboardActions(onSearch = { focus.clearFocus(); vm.search() }),
            )

            Box {
                Column {
                    LazyRow(
                        contentPadding = PaddingValues(horizontal = 16.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        item {
                            FilterChip(selected = vm.category == null, onClick = { vm.selectCategory(null) }, label = { Text("Semua") })
                        }
                        items(vm.categories, key = { it.id }) { c ->
                            FilterChip(
                                selected = vm.category == c.slug,
                                onClick = { vm.selectCategory(if (vm.category == c.slug) null else c.slug) },
                                label = { Text(c.name) },
                            )
                        }
                    }

                    vm.identified?.let { IdentifiedBanner(it) }

                    Row(Modifier.fillMaxWidth().padding(horizontal = 16.dp), verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            when {
                                vm.loading && vm.identified == null && vm.error == null -> "Mencari…"
                                else -> "${vm.total} buku"
                            },
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.weight(1f),
                        )
                        var open by remember { mutableStateOf(false) }
                        Box {
                            TextButton(onClick = { open = true }) {
                                Icon(Icons.AutoMirrored.Filled.Sort, null)
                                Text(" " + SortOptions.first { it.first == vm.sort }.second)
                            }
                            DropdownMenu(expanded = open, onDismissRequest = { open = false }) {
                                SortOptions.forEach { (value, label) ->
                                    DropdownMenuItem(text = { Text(label) }, onClick = { open = false; vm.selectSort(value) })
                                }
                            }
                        }
                    }

                    when {
                        vm.loading -> LoadingBox()
                        vm.error != null -> ErrorBox(vm.error!!, onRetry = vm::search)
                        vm.products.isEmpty() -> MessageBox(
                            Icons.Outlined.SearchOff,
                            "Buku tidak ditemukan",
                            body = if (vm.identified != null) "Buku di foto belum tersedia di katalog." else "Coba kata kunci atau kategori lain.",
                        )
                        else -> LazyVerticalGrid(
                            columns = GridCells.Adaptive(160.dp),
                            state = grid,
                            contentPadding = PaddingValues(16.dp),
                            horizontalArrangement = Arrangement.spacedBy(12.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp),
                        ) {
                            itemsIndexed(vm.products, key = { _, p -> p.id }) { _, p ->
                                BookCard(p, onClick = { nav.navigate(Routes.product(p.slug)) })
                            }
                            if (vm.loadingMore) {
                                item(span = { GridItemSpan(maxLineSpan) }) {
                                    Box(Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
                                }
                            }
                        }
                    }
                }

                // Daftar saran muncul di atas hasil selama kolom pencarian aktif.
                if (focused && vm.query.isNotBlank() && vm.suggestions.isNotEmpty()) {
                    SuggestionList(vm.query, vm.suggestions) { s ->
                        focus.clearFocus()
                        if (s.type == "title" && s.slug != null) {
                            vm.clearSuggestions()
                            nav.navigate(Routes.product(s.slug))
                        } else {
                            vm.applySuggestion(s)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SuggestionList(query: String, suggestions: List<Suggestion>, onPick: (Suggestion) -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 6.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        LazyColumn(Modifier.heightIn(max = 360.dp)) {
            items(suggestions, key = { it.type + it.label }) { s ->
                val (icon, hint) = when (s.type) {
                    "author" -> Icons.Outlined.PersonOutline to "Penulis"
                    "category" -> Icons.Outlined.Category to "Kategori"
                    else -> Icons.Outlined.Book to null
                }
                SuggestionRow(icon, s.label, query, hint) { onPick(s) }
                HorizontalDivider()
            }
        }
    }
}

@Composable
private fun SuggestionRow(icon: ImageVector, label: String, query: String, hint: String?, onClick: () -> Unit) {
    // Tebalkan bagian yang cocok dengan huruf yang sudah diketik.
    val start = label.indexOf(query.trim(), ignoreCase = true)
    val text = buildAnnotatedString {
        if (start < 0) {
            append(label)
        } else {
            val end = start + query.trim().length
            append(label.substring(0, start))
            withStyle(SpanStyle(fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)) { append(label.substring(start, end)) }
            append(label.substring(end))
        }
    }
    Row(
        Modifier.fillMaxWidth().clickable(onClick = onClick).padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Icon(icon, null, tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(20.dp))
        Spacer(Modifier.width(12.dp))
        Text(text, modifier = Modifier.weight(1f), maxLines = 1, overflow = TextOverflow.Ellipsis)
        hint?.let { Text(it, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
    }
}

@Composable
private fun IdentifiedBanner(book: IdentifiedBook) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
    ) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.Top) {
            Icon(Icons.Outlined.AutoAwesome, null, tint = MaterialTheme.colorScheme.primary)
            Spacer(Modifier.width(10.dp))
            Column {
                Text("Terdeteksi dari foto", style = MaterialTheme.typography.labelSmall)
                Text(book.title ?: "Judul tidak terbaca", fontWeight = FontWeight.Bold)
                book.author?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                if (book.description.isNotBlank()) {
                    Text(book.description, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onPrimaryContainer)
                }
            }
        }
    }
}
