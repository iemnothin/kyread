package id.skyvora.kyread.data

import kotlinx.serialization.Serializable

// Model mengikuti JSON dari /api/v1 (snake_case dipetakan otomatis oleh JsonNamingStrategy.SnakeCase).

@Serializable
data class DataWrapper<T>(val data: T)

@Serializable
data class PageMeta(val currentPage: Int = 1, val lastPage: Int = 1, val total: Int = 0)

@Serializable
data class Paged<T>(val data: List<T>, val meta: PageMeta = PageMeta())

@Serializable
data class Category(
    val id: Int,
    val name: String,
    val slug: String,
    val iconUrl: String,
    val productsCount: Int? = null,
)

@Serializable
data class Store(
    val id: Int,
    val name: String,
    val slug: String,
    val city: String? = null,
    val logoUrl: String,
)

@Serializable
data class Product(
    val id: Int,
    val slug: String,
    val title: String,
    val author: String,
    val price: Long,
    val compareAtPrice: Long? = null,
    val discountPercent: Int = 0,
    val stock: Int,
    val soldCount: Int = 0,
    val weight: Int = 0,
    val imageUrl: String,
    val rating: Double? = null,
    val category: Category? = null,
    val store: Store? = null,
    // Hanya terisi di endpoint detail:
    val publisher: String? = null,
    val isbn: String? = null,
    val pages: Int? = null,
    val publishedYear: Int? = null,
    val description: String? = null,
    val reviewsCount: Int = 0,
    val isOwnProduct: Boolean = false,
    val isWishlisted: Boolean = false,
    val vouchers: List<Voucher> = emptyList(),
)

@Serializable
data class Voucher(
    val code: String,
    val type: String,
    val value: Long,
    val minSpend: Long = 0,
    val maxDiscount: Long? = null,
    val summary: String,
    val expiresAt: String? = null,
)

/** Hasil cek voucher terhadap isi keranjang (POST /checkout/voucher). */
@Serializable
data class VoucherCheck(
    val code: String,
    val storeId: Int,
    val storeName: String,
    val summary: String,
    val discount: Long,
    val message: String,
)

@Serializable
data class AppNotification(
    val id: String,
    val title: String,
    val body: String,
    val orderCode: String? = null,
    val audience: String = "buyer",
    val read: Boolean = false,
    val createdAt: String,
)

@Serializable
data class NotificationPage(
    val data: List<AppNotification> = emptyList(),
    val unreadCount: Int = 0,
    val meta: PageMeta = PageMeta(),
)

@Serializable
data class Review(
    val id: Int,
    val productId: Int,
    val rating: Int,
    val comment: String? = null,
    val userName: String? = null,
    val createdAt: String,
)

@Serializable
data class ProductDetail(val data: Product, val reviews: List<Review> = emptyList(), val related: List<Product> = emptyList())

@Serializable
data class Address(
    val street: String? = null,
    val city: String? = null,
    val province: String? = null,
    val zipcode: String? = null,
    val complete: Boolean = false,
)

@Serializable
data class User(
    val id: Int,
    val name: String,
    val nickname: String? = null,
    val displayName: String,
    val username: String,
    val email: String,
    val phone: String? = null,
    val about: String? = null,
    val avatarUrl: String,
    val address: Address = Address(),
    val hasStore: Boolean = false,
    val unreadNotifications: Int = 0,
    val wishlistCount: Int = 0,
)

@Serializable
data class AuthResponse(val token: String, val user: User)

@Serializable
data class CartItem(
    val id: Int,
    val quantity: Int,
    val subtotal: Long,
    val available: Boolean,
    val product: Product,
)

@Serializable
data class CartGroup(val store: Store, val items: List<CartItem>, val subtotal: Long, val weight: Int)

@Serializable
data class Cart(
    val groups: List<CartGroup> = emptyList(),
    val count: Int = 0,
    val total: Long = 0,
    val message: String? = null,
)

@Serializable
data class Courier(
    val code: String,
    val label: String,
    val ratePerKg: Long,
    val estimate: String,
    val logoUrl: String,
) {
    /** Sama dengan App\Enums\Courier::costFor di backend. */
    fun costFor(weightInGrams: Int): Long = maxOf(1, (weightInGrams + 999) / 1000) * ratePerKg
}

@Serializable
data class PaymentMethod(val code: String, val label: String, val logoUrl: String)

@Serializable
data class AppMeta(
    val couriers: List<Courier> = emptyList(),
    val paymentMethods: List<PaymentMethod> = emptyList(),
    val banners: List<String> = emptyList(),
)

@Serializable
data class OrderItem(
    val productId: Int? = null,
    val productSlug: String? = null,
    val title: String,
    val imageUrl: String,
    val price: Long,
    val quantity: Int,
    val subtotal: Long,
)

@Serializable
data class Recipient(val name: String, val phone: String, val address: String)

@Serializable
data class CodeLabel(val code: String, val label: String)

@Serializable
data class OrderActions(
    val pay: Boolean = false,
    val cancel: Boolean = false,
    val complete: Boolean = false,
    val review: Boolean = false,
)

@Serializable
data class Order(
    val code: String,
    val status: String,
    val statusLabel: String,
    val store: Store? = null,
    val items: List<OrderItem> = emptyList(),
    val itemsCount: Int? = null,
    val recipient: Recipient,
    val courier: CodeLabel,
    val paymentMethod: CodeLabel,
    val trackingNumber: String? = null,
    val notes: String? = null,
    val subtotal: Long,
    val shippingCost: Long,
    val voucherCode: String? = null,
    val discount: Long = 0,
    val total: Long,
    val invoiceUrl: String? = null,
    val paymentDeadline: String? = null,
    val virtualAccount: String? = null,
    val reviews: List<Review> = emptyList(),
    val can: OrderActions = OrderActions(),
    val createdAt: String,
    val paidAt: String? = null,
    val shippedAt: String? = null,
    val completedAt: String? = null,
    val cancelledAt: String? = null,
)

/** Status pesanan (nilai sama dengan App\Enums\OrderStatus). */
enum class OrderStatus(val value: String, val label: String) {
    Pending("pending", "Menunggu Pembayaran"),
    Paid("paid", "Dibayar"),
    Processing("processing", "Diproses"),
    Shipped("shipped", "Dikirim"),
    Completed("completed", "Selesai"),
    Cancelled("cancelled", "Dibatalkan"),
    ;

    companion object {
        fun from(value: String) = entries.firstOrNull { it.value == value }
    }
}

@Serializable
data class Suggestion(val type: String, val label: String, val value: String, val slug: String? = null)

@Serializable
data class IdentifiedBook(
    val title: String? = null,
    val author: String? = null,
    val keywords: List<String> = emptyList(),
    val description: String = "",
)

@Serializable
data class ImageSearchResult(val identified: IdentifiedBook, val query: String = "", val data: List<Product> = emptyList())

@Serializable
data class ChatMessage(val role: String, val content: String)

@Serializable
data class AssistantReply(val reply: String, val products: List<Product> = emptyList())

@Serializable
data class CheckoutRequest(
    val recipientName: String,
    val recipientPhone: String,
    val street: String,
    val city: String,
    val province: String,
    val zipcode: String,
    val courier: String,
    val paymentMethod: String,
    val notes: String? = null,
    val voucherCode: String? = null,
    val saveAddress: Boolean = false,
)

@Serializable
data class ProfileRequest(
    val name: String,
    val nickname: String? = null,
    val username: String,
    val email: String,
    val phone: String? = null,
    val about: String? = null,
    val street: String? = null,
    val city: String? = null,
    val province: String? = null,
    val zipcode: String? = null,
)
