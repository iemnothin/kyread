package id.skyvora.kyread

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.mutableStateOf
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import androidx.lifecycle.lifecycleScope
import id.skyvora.kyread.ui.KyreadAppUi
import id.skyvora.kyread.ui.theme.KyreadTheme
import kotlinx.coroutines.launch

class MainActivity : ComponentActivity() {
    private var ready = false

    /** Slug buku dari deep link https://…/books/{slug}. */
    private val deepLinkSlug = mutableStateOf<String?>(null)

    override fun onCreate(savedInstanceState: Bundle?) {
        val splash = installSplashScreen()
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val repository = (application as KyreadApp).repository
        splash.setKeepOnScreenCondition { !ready }
        lifecycleScope.launch {
            repository.restoreSession()
            ready = true
        }

        handleIntent(intent)
        setContent {
            KyreadTheme {
                KyreadAppUi(
                    repository = repository,
                    deepLinkSlug = deepLinkSlug.value,
                    onDeepLinkHandled = { deepLinkSlug.value = null },
                )
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        handleIntent(intent)
    }

    private fun handleIntent(intent: Intent?) {
        val segments = intent?.data?.pathSegments ?: return
        if (segments.size >= 2 && segments[0] == "books") deepLinkSlug.value = segments[1]
    }
}
