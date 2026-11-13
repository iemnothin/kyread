package id.skyvora.kyread

import android.app.Application
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.TokenStore
import id.skyvora.kyread.i18n.I18n

class KyreadApp : Application() {
    lateinit var repository: KyreadRepository
        private set

    override fun onCreate() {
        super.onCreate()
        I18n.init(this)
        repository = KyreadRepository(TokenStore(this))
    }
}
