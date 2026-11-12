package id.skyvora.kyread

import android.app.Application
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.TokenStore

class KyreadApp : Application() {
    lateinit var repository: KyreadRepository
        private set

    override fun onCreate() {
        super.onCreate()
        repository = KyreadRepository(TokenStore(this))
    }
}
