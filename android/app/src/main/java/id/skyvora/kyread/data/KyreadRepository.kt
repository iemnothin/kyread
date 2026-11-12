package id.skyvora.kyread.data

import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Satu-satunya pintu ke API. Menyimpan state global ringan:
 * pengguna yang login dan jumlah item keranjang (untuk badge).
 */
class KyreadRepository(private val tokens: TokenStore) {

    private val _user = MutableStateFlow<User?>(null)
    val user: StateFlow<User?> = _user.asStateFlow()

    private val _cartCount = MutableStateFlow(0)
    val cartCount: StateFlow<Int> = _cartCount.asStateFlow()

    private var cachedMeta: AppMeta? = null

    private val api = ApiClient(
        tokenProvider = { tokens.current() },
        onUnauthorized = { clearSession() },
    )

    // --- Sesi -------------------------------------------------------------------

    /** Dipanggil saat aplikasi dibuka: pulihkan sesi bila token masih valid. */
    suspend fun restoreSession() {
        if (tokens.current() == null) return
        runCatching { refreshUser() }
        runCatching { refreshCartCount() }
    }

    suspend fun login(login: String, password: String) {
        val res: AuthResponse = api.post("auth/login", LoginBody(login, password))
        startSession(res)
    }

    suspend fun register(name: String, username: String, email: String, password: String, confirmation: String) {
        val res: AuthResponse = api.post("auth/register", RegisterBody(name, username, email, password, confirmation))
        startSession(res)
    }

    /** ID token dari Credential Manager (Sign in with Google) ditukar dengan token kyread. */
    suspend fun loginWithGoogle(idToken: String) {
        val res: AuthResponse = api.post("auth/google", GoogleBody(idToken))
        startSession(res)
    }

    suspend fun logout() {
        runCatching { api.post<Message>("auth/logout") }
        clearSession()
    }

    suspend fun refreshUser(): User = api.get<DataWrapper<User>>("me").data.also { _user.value = it }

    suspend fun updateProfile(body: ProfileRequest): User =
        api.post<DataWrapper<User>, ProfileRequest>("me", body).data.also { _user.value = it }

    private suspend fun startSession(res: AuthResponse) {
        tokens.save(res.token)
        _user.value = res.user
        runCatching { refreshCartCount() }
    }

    private suspend fun clearSession() {
        tokens.clear()
        _user.value = null
        _cartCount.value = 0
    }

    // --- Katalog ---------------------------------------------------------------

    suspend fun meta(): AppMeta = cachedMeta ?: api.get<AppMeta>("meta").also { cachedMeta = it }

    suspend fun categories(): List<Category> = api.get<Paged<Category>>("categories").data

    suspend fun products(
        q: String? = null,
        category: String? = null,
        sort: String? = null,
        page: Int = 1,
        perPage: Int = 20,
    ): Paged<Product> = api.get(
        "products",
        mapOf("q" to q, "category" to category, "sort" to sort, "page" to page, "per_page" to perPage),
    )

    suspend fun product(slug: String): ProductDetail = api.get("products/$slug")

    suspend fun suggest(q: String): List<Suggestion> = api.get<Paged<Suggestion>>("search/suggest", mapOf("q" to q)).data

    suspend fun searchByImage(bytes: ByteArray, mime: String): ImageSearchResult =
        api.upload("search/image", "image", "photo.jpg", bytes, mime)

    suspend fun askAssistant(history: List<ChatMessage>): AssistantReply =
        api.post<AssistantReply, AssistantBody>("assistant", AssistantBody(history.takeLast(12)))

    // --- Keranjang -------------------------------------------------------------

    suspend fun cart(): Cart = api.get<Cart>("cart").also { _cartCount.value = it.count }

    suspend fun addToCart(productId: Int, quantity: Int): Cart =
        api.post<Cart, AddToCartBody>("cart", AddToCartBody(productId, quantity)).also { _cartCount.value = it.count }

    suspend fun updateCartItem(itemId: Int, quantity: Int): Cart =
        api.patch<Cart, QuantityBody>("cart/$itemId", QuantityBody(quantity)).also { _cartCount.value = it.count }

    suspend fun removeCartItem(itemId: Int): Cart =
        api.delete<Cart>("cart/$itemId").also { _cartCount.value = it.count }

    private suspend fun refreshCartCount() {
        cart()
    }

    // --- Pesanan ---------------------------------------------------------------

    suspend fun checkout(body: CheckoutRequest): List<Order> {
        val orders = api.post<Paged<Order>, CheckoutRequest>("checkout", body).data
        _cartCount.value = 0
        if (body.saveAddress) runCatching { refreshUser() }
        return orders
    }

    suspend fun orders(status: String? = null, page: Int = 1): Paged<Order> =
        api.get("orders", mapOf("status" to status, "page" to page))

    suspend fun order(code: String): Order = api.get<DataWrapper<Order>>("orders/$code").data

    suspend fun payOrder(code: String): Order = api.post<DataWrapper<Order>>("orders/$code/pay").data

    suspend fun cancelOrder(code: String): Order = api.post<DataWrapper<Order>>("orders/$code/cancel").data

    suspend fun completeOrder(code: String): Order = api.post<DataWrapper<Order>>("orders/$code/complete").data

    suspend fun reviewOrder(code: String, productId: Int, rating: Int, comment: String?): Order =
        api.post<DataWrapper<Order>, ReviewBody>("orders/$code/review", ReviewBody(productId, rating, comment)).data
}

@kotlinx.serialization.Serializable
private data class LoginBody(val login: String, val password: String, val deviceName: String = "android")

@kotlinx.serialization.Serializable
private data class RegisterBody(
    val name: String,
    val username: String,
    val email: String,
    val password: String,
    val passwordConfirmation: String,
    val deviceName: String = "android",
)

@kotlinx.serialization.Serializable
private data class GoogleBody(val idToken: String, val deviceName: String = "android")

@kotlinx.serialization.Serializable
private data class AssistantBody(val messages: List<ChatMessage>)

@kotlinx.serialization.Serializable
private data class AddToCartBody(val productId: Int, val quantity: Int)

@kotlinx.serialization.Serializable
private data class QuantityBody(val quantity: Int)

@kotlinx.serialization.Serializable
private data class ReviewBody(val productId: Int, val rating: Int, val comment: String?)

@kotlinx.serialization.Serializable
data class Message(val message: String? = null)
