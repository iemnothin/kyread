package id.skyvora.kyread.data

import id.skyvora.kyread.i18n.I18n
import id.skyvora.kyread.i18n.tr
import id.skyvora.kyread.BuildConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.ExperimentalSerializationApi
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonNamingStrategy
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.IOException
import java.util.concurrent.TimeUnit

/** Pesan error yang siap ditampilkan ke pengguna. */
class ApiException(message: String, val status: Int = 0) : Exception(message)

@OptIn(ExperimentalSerializationApi::class)
val KyreadJson = Json {
    namingStrategy = JsonNamingStrategy.SnakeCase
    ignoreUnknownKeys = true
    explicitNulls = false
    coerceInputValues = true
}

/**
 * HTTP client tipis di atas OkHttp untuk /api/v1.
 * Token Sanctum disisipkan otomatis; 401 memicu [onUnauthorized] (sesi dihapus).
 */
class ApiClient(
    private val baseUrl: String = BuildConfig.API_BASE_URL,
    private val tokenProvider: suspend () -> String?,
    private val onUnauthorized: suspend () -> Unit,
) {
    @PublishedApi
    internal val json = KyreadJson

    private val http = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        // Fitur AI (pengenalan gambar/asisten) bisa butuh beberapa detik lebih lama.
        .readTimeout(90, TimeUnit.SECONDS)
        .apply {
            if (BuildConfig.DEBUG) {
                addInterceptor(
                    okhttp3.logging.HttpLoggingInterceptor().setLevel(okhttp3.logging.HttpLoggingInterceptor.Level.BASIC),
                )
            }
        }
        .build()

    suspend inline fun <reified T> get(path: String, query: Map<String, Any?> = emptyMap()): T =
        json.decodeFromString(request("GET", path, query = query))

    suspend inline fun <reified T, reified B> post(path: String, body: B): T =
        json.decodeFromString(request("POST", path, body = json.encodeToString(body)))

    suspend inline fun <reified T> post(path: String): T =
        json.decodeFromString(request("POST", path, body = "{}"))

    suspend inline fun <reified T, reified B> patch(path: String, body: B): T =
        json.decodeFromString(request("PATCH", path, body = json.encodeToString(body)))

    suspend inline fun <reified T> delete(path: String): T =
        json.decodeFromString(request("DELETE", path))

    /** Upload satu berkas (multipart/form-data), mis. foto untuk cari dengan gambar. */
    suspend inline fun <reified T> upload(path: String, field: String, fileName: String, bytes: ByteArray, mime: String): T {
        val body = MultipartBody.Builder()
            .setType(MultipartBody.FORM)
            .addFormDataPart(field, fileName, bytes.toRequestBody(mime.toMediaType()))
            .build()
        return json.decodeFromString(request("POST", path, rawBody = body))
    }

    @PublishedApi
    internal suspend fun request(
        method: String,
        path: String,
        query: Map<String, Any?> = emptyMap(),
        body: String? = null,
        rawBody: RequestBody? = null,
    ): String {
        val url = (baseUrl.trimEnd('/') + "/" + path.trimStart('/')).toHttpUrl().newBuilder().apply {
            query.forEach { (k, v) -> if (v != null && v.toString().isNotBlank()) addQueryParameter(k, v.toString()) }
        }.build()

        val token = tokenProvider()
        val request = Request.Builder()
            .url(url)
            .header("Accept", "application/json")
            .header("X-Locale", I18n.language.code)
            .apply { if (token != null) header("Authorization", "Bearer $token") }
            .method(method, rawBody ?: body?.toRequestBody(JSON_MEDIA))
            .build()

        val (code, text) = withContext(Dispatchers.IO) {
            try {
                http.newCall(request).execute().use { it.code to (it.body?.string().orEmpty()) }
            } catch (e: IOException) {
                throw ApiException(tr("Tidak dapat terhubung ke server. Periksa koneksi internetmu."))
            }
        }

        if (code in 200..299) return text
        if (code == 401) {
            onUnauthorized()
            throw ApiException(tr("Sesi kamu berakhir. Silakan masuk lagi."), code)
        }
        throw ApiException(errorMessage(code, text), code)
    }

    /** Ambil pesan pertama dari respons validasi Laravel ({message, errors}) atau pesan umum. */
    private fun errorMessage(code: Int, text: String): String {
        val obj = runCatching { json.parseToJsonElement(text).jsonObject }.getOrNull()
        val firstError = runCatching {
            (obj?.get("errors") as? JsonObject)?.values?.first()?.jsonArray?.first()?.jsonPrimitive?.content
        }.getOrNull()
        val message = runCatching { obj?.get("message")?.jsonPrimitive?.content }.getOrNull()

        return firstError ?: message?.takeIf { it.isNotBlank() && code != 500 } ?: when (code) {
            403 -> tr("Kamu tidak punya akses untuk tindakan ini.")
            404 -> tr("Data tidak ditemukan.")
            429 -> tr("Terlalu banyak percobaan. Tunggu sebentar.")
            else -> tr("Terjadi kesalahan di server (:code).", "code" to code)
        }
    }

    private companion object {
        val JSON_MEDIA = "application/json; charset=utf-8".toMediaType()
    }
}
