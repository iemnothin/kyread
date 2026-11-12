package id.skyvora.kyread.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "session")

/** Menyimpan token Sanctum di DataStore agar sesi bertahan setelah aplikasi ditutup. */
class TokenStore(private val context: Context) {
    private val key = stringPreferencesKey("token")

    val token: Flow<String?> = context.dataStore.data.map { it[key] }

    suspend fun current(): String? = token.first()

    suspend fun save(value: String) {
        context.dataStore.edit { it[key] = value }
    }

    suspend fun clear() {
        context.dataStore.edit { it.remove(key) }
    }
}
