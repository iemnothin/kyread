package id.skyvora.kyread.ui

import id.skyvora.kyread.i18n.tr
import androidx.compose.animation.AnimatedContentTransitionScope.SlideDirection
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.ShoppingBag
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material.icons.outlined.Receipt
import androidx.compose.material.icons.outlined.Search
import androidx.compose.material.icons.outlined.ShoppingBag
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.i18n.I18n
import id.skyvora.kyread.ui.screens.account.AccountScreen
import id.skyvora.kyread.ui.screens.account.EditProfileScreen
import id.skyvora.kyread.ui.screens.assistant.AssistantScreen
import id.skyvora.kyread.ui.screens.auth.LoginScreen
import id.skyvora.kyread.ui.screens.auth.RegisterScreen
import id.skyvora.kyread.ui.screens.cart.CartScreen
import id.skyvora.kyread.ui.screens.checkout.CheckoutScreen
import id.skyvora.kyread.ui.screens.home.HomeScreen
import id.skyvora.kyread.ui.screens.orders.OrderDetailScreen
import id.skyvora.kyread.ui.screens.orders.OrdersScreen
import id.skyvora.kyread.ui.screens.product.ProductScreen
import id.skyvora.kyread.ui.screens.notifications.NotificationsScreen
import id.skyvora.kyread.ui.screens.search.SearchScreen
import id.skyvora.kyread.ui.screens.wishlist.WishlistScreen

object Routes {
    const val HOME = "home"
    const val SEARCH = "search?category={category}&q={q}&sort={sort}"
    const val PRODUCT = "product/{slug}"
    const val CART = "cart"
    const val CHECKOUT = "checkout"
    const val ORDERS = "orders"
    const val ORDER = "order/{code}?celebrate={celebrate}"
    const val ACCOUNT = "account"
    const val EDIT_PROFILE = "account/edit"
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val ASSISTANT = "assistant"
    const val WISHLIST = "wishlist"
    const val NOTIFICATIONS = "notifications"

    fun search(category: String? = null, q: String? = null, sort: String? = null) =
        "search?category=${category.orEmpty()}&q=${q.orEmpty()}&sort=${sort.orEmpty()}"

    fun product(slug: String) = "product/$slug"
    fun order(code: String, celebrate: Boolean = false) = "order/$code?celebrate=$celebrate"
}

private data class Tab(val route: String, val target: String, val label: String, val icon: ImageVector, val selectedIcon: ImageVector)

private val tabs get() = listOf(
    Tab(Routes.HOME, Routes.HOME, tr("Beranda"), Icons.Outlined.Home, Icons.Filled.Home),
    Tab(Routes.SEARCH, Routes.search(), tr("Cari"), Icons.Outlined.Search, Icons.Filled.Search),
    Tab(Routes.CART, Routes.CART, tr("Keranjang"), Icons.Outlined.ShoppingBag, Icons.Filled.ShoppingBag),
    Tab(Routes.ORDERS, Routes.ORDERS, tr("Pesanan"), Icons.Outlined.Receipt, Icons.Filled.Receipt),
    Tab(Routes.ACCOUNT, Routes.ACCOUNT, tr("Akun"), Icons.Outlined.Person, Icons.Filled.Person),
)

/** Pindah tab tanpa menumpuk back stack. */
fun NavHostController.navigateTab(route: String) = navigate(route) {
    popUpTo(graph.findStartDestination().id) { saveState = true }
    launchSingleTop = true
    restoreState = true
}

@Composable
fun KyreadAppUi(repository: KyreadRepository, deepLinkSlug: String?, onDeepLinkHandled: () -> Unit) {
    // Ganti bahasa = susun ulang navigasi dari awal: layar & ViewModel lama dibuang sehingga
    // data dari server (label status, ringkasan voucher, …) dimuat ulang dalam bahasa baru.
    key(I18n.language.code) {
        KyreadNavigation(
            repository, deepLinkSlug, onDeepLinkHandled,
            startRoute = if (I18n.changedByUser) Routes.ACCOUNT else Routes.HOME,
        )
    }
}

@Composable
private fun KyreadNavigation(repository: KyreadRepository, deepLinkSlug: String?, onDeepLinkHandled: () -> Unit, startRoute: String) {
    val nav = rememberNavController()
    val backStack by nav.currentBackStackEntryAsState()
    val currentRoute = backStack?.destination?.route
    val cartCount by repository.cartCount.collectAsStateWithLifecycle()
    val unreadCount by repository.unreadCount.collectAsStateWithLifecycle()

    LaunchedEffect(deepLinkSlug) {
        if (deepLinkSlug != null) {
            nav.navigate(Routes.product(deepLinkSlug))
            onDeepLinkHandled()
        }
    }

    Scaffold(
        bottomBar = {
            if (tabs.any { it.route == currentRoute }) {
                NavigationBar {
                    tabs.forEach { tab ->
                        val selected = currentRoute == tab.route
                        NavigationBarItem(
                            selected = selected,
                            onClick = { nav.navigateTab(tab.target) },
                            label = { Text(tab.label) },
                            icon = {
                                BadgedBox(badge = {
                                    if (tab.route == Routes.CART && cartCount > 0) Badge { Text("$cartCount") }
                                    if (tab.route == Routes.ACCOUNT && unreadCount > 0) Badge()
                                }) {
                                    Icon(if (selected) tab.selectedIcon else tab.icon, contentDescription = tab.label)
                                }
                            },
                        )
                    }
                }
            }
        },
    ) { padding ->
        // Layar dengan bottom bar memakai padding bawah dari Scaffold ini; layar lain punya Scaffold sendiri.
        val tabPadding = Modifier.padding(bottom = padding.calculateBottomPadding())

        NavHost(
            nav,
            startDestination = startRoute,
            enterTransition = { fadeIn(tween(220)) + slideIntoContainer(SlideDirection.Start, tween(260)) { it / 8 } },
            exitTransition = { fadeOut(tween(180)) },
            popEnterTransition = { fadeIn(tween(220)) },
            popExitTransition = { fadeOut(tween(180)) + slideOutOfContainer(SlideDirection.End, tween(240)) { it / 8 } },
        ) {
            composable(Routes.HOME) { HomeScreen(repository, nav, tabPadding) }
            composable(
                Routes.SEARCH,
                arguments = listOf("category", "q", "sort").map { name ->
                    navArgument(name) { type = NavType.StringType; nullable = true; defaultValue = null }
                },
            ) { entry ->
                val args = entry.arguments
                SearchScreen(
                    repository, nav, tabPadding,
                    initialCategory = args?.getString("category")?.ifBlank { null },
                    initialQuery = args?.getString("q")?.ifBlank { null },
                    initialSort = args?.getString("sort")?.ifBlank { null },
                )
            }
            composable(Routes.PRODUCT, arguments = listOf(navArgument("slug") { type = NavType.StringType })) { entry ->
                ProductScreen(repository, nav, entry.arguments?.getString("slug").orEmpty())
            }
            composable(Routes.CART) { CartScreen(repository, nav, tabPadding) }
            composable(Routes.CHECKOUT) { CheckoutScreen(repository, nav) }
            composable(Routes.ORDERS) { OrdersScreen(repository, nav, tabPadding) }
            composable(
                Routes.ORDER,
                arguments = listOf(
                    navArgument("code") { type = NavType.StringType },
                    navArgument("celebrate") { type = NavType.BoolType; defaultValue = false },
                ),
            ) { entry ->
                OrderDetailScreen(
                    repository, nav, entry.arguments?.getString("code").orEmpty(),
                    celebrate = entry.arguments?.getBoolean("celebrate") == true,
                )
            }
            composable(Routes.ACCOUNT) { AccountScreen(repository, nav, tabPadding) }
            composable(Routes.EDIT_PROFILE) { EditProfileScreen(repository, nav) }
            composable(Routes.LOGIN) { LoginScreen(repository, nav) }
            composable(Routes.REGISTER) { RegisterScreen(repository, nav) }
            composable(Routes.ASSISTANT) { AssistantScreen(repository, nav) }
            composable(Routes.WISHLIST) { WishlistScreen(repository, nav) }
            composable(Routes.NOTIFICATIONS) { NotificationsScreen(repository, nav) }
        }
    }
}
