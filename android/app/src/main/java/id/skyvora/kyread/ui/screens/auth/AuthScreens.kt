package id.skyvora.kyread.ui.screens.auth

import id.skyvora.kyread.i18n.tr
import android.content.Context
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import id.skyvora.kyread.ui.components.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import id.skyvora.kyread.ui.components.IconButton
import androidx.compose.material3.MaterialTheme
import id.skyvora.kyread.ui.components.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import id.skyvora.kyread.ui.components.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.GetCredentialRequest
import androidx.credentials.exceptions.GetCredentialCancellationException
import androidx.credentials.exceptions.GetCredentialException
import androidx.navigation.NavHostController
import com.google.android.libraries.identity.googleid.GetSignInWithGoogleOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import id.skyvora.kyread.BuildConfig
import id.skyvora.kyread.R
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.ui.Routes
import id.skyvora.kyread.ui.components.BackIcon
import id.skyvora.kyread.ui.theme.KyBlue
import kotlinx.coroutines.launch

private val googleEnabled get() = BuildConfig.GOOGLE_WEB_CLIENT_ID.isNotBlank()

/** Tampilkan dialog "Sign in with Google" lalu kembalikan ID token (null bila dibatalkan). */
private suspend fun requestGoogleIdToken(context: Context): String? {
    val option = GetSignInWithGoogleOption.Builder(BuildConfig.GOOGLE_WEB_CLIENT_ID).build()
    val request = GetCredentialRequest.Builder().addCredentialOption(option).build()
    return try {
        val credential = CredentialManager.create(context).getCredential(context, request).credential
        if (credential is CustomCredential && credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL) {
            GoogleIdTokenCredential.createFrom(credential.data).idToken
        } else {
            throw IllegalStateException(tr("Jenis kredensial tidak dikenali."))
        }
    } catch (e: GetCredentialCancellationException) {
        null
    } catch (e: GetCredentialException) {
        throw IllegalStateException(tr("Login Google gagal: :reason", "reason" to (e.message ?: tr("tidak ada akun Google di perangkat"))))
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun AuthScaffold(
    nav: NavHostController,
    title: String,
    error: String?,
    loading: Boolean,
    onGoogle: () -> Unit,
    content: @Composable () -> Unit,
) {
    Scaffold(topBar = { TopAppBar(title = {}, navigationIcon = { BackIcon { nav.popBackStack() } }) }) { padding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(padding)
                .imePadding()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Image(painterResource(R.drawable.ic_splash), null, modifier = Modifier.size(88.dp))
            Text("kyread", color = KyBlue, fontWeight = FontWeight.Bold, fontStyle = FontStyle.Italic, fontSize = 32.sp)
            Text(title, style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 4.dp, bottom = 20.dp))

            if (googleEnabled) {
                OutlinedButton(onClick = onGoogle, enabled = !loading, modifier = Modifier.fillMaxWidth().height(50.dp)) {
                    Image(painterResource(R.drawable.ic_google), null, modifier = Modifier.size(20.dp))
                    Spacer(Modifier.width(10.dp))
                    Text(tr("Lanjutkan dengan Google"))
                }
                Row(Modifier.padding(vertical = 16.dp), verticalAlignment = Alignment.CenterVertically) {
                    HorizontalDivider(Modifier.weight(1f))
                    Text("  " + tr("atau") + "  ", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    HorizontalDivider(Modifier.weight(1f))
                }
            }

            if (error != null) {
                Text(error, color = MaterialTheme.colorScheme.error, modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp))
            }
            content()
            Spacer(Modifier.height(24.dp))
        }
    }
}

@Composable
private fun PasswordField(value: String, onChange: (String) -> Unit, label: String, imeAction: ImeAction = ImeAction.Next) {
    var visible by remember { mutableStateOf(false) }
    OutlinedTextField(
        value, onChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        singleLine = true,
        visualTransformation = if (visible) VisualTransformation.None else PasswordVisualTransformation(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = imeAction),
        trailingIcon = {
            IconButton(onClick = { visible = !visible }) {
                Icon(if (visible) Icons.Filled.VisibilityOff else Icons.Filled.Visibility, if (visible) tr("Sembunyikan") else tr("Tampilkan"))
            }
        },
    )
}

@Composable
private fun rememberAuthActions(repo: KyreadRepository, nav: NavHostController): AuthActions {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    return remember { AuthActions(repo, nav, context, scope) }
}

private class AuthActions(
    private val repo: KyreadRepository,
    private val nav: NavHostController,
    private val context: Context,
    private val scope: kotlinx.coroutines.CoroutineScope,
) {
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun run(block: suspend () -> Unit) = scope.launch {
        loading = true
        error = null
        runCatching { block() }
            .onSuccess { nav.popBackStack(Routes.LOGIN, inclusive = true) || nav.popBackStack() }
            .onFailure { error = it.message }
        loading = false
    }

    fun google() = scope.launch {
        loading = true
        error = null
        runCatching { requestGoogleIdToken(context) }
            .onSuccess { token ->
                if (token != null) {
                    runCatching { repo.loginWithGoogle(token) }
                        .onSuccess { nav.popBackStack(Routes.LOGIN, inclusive = true) || nav.popBackStack() }
                        .onFailure { error = it.message }
                }
            }
            .onFailure { error = it.message }
        loading = false
    }
}

@Composable
fun LoginScreen(repo: KyreadRepository, nav: NavHostController) {
    val actions = rememberAuthActions(repo, nav)
    var login by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }

    AuthScaffold(nav, tr("Masuk ke akunmu"), actions.error, actions.loading, onGoogle = actions::google) {
        Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
            OutlinedTextField(
                login, { login = it }, Modifier.fillMaxWidth(),
                label = { Text(tr("Email atau username")) }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email, imeAction = ImeAction.Next),
            )
            PasswordField(password, { password = it }, tr("Password"), ImeAction.Done)
            Button(
                onClick = { actions.run { repo.login(login.trim(), password) } },
                enabled = !actions.loading && login.isNotBlank() && password.isNotBlank(),
                modifier = Modifier.fillMaxWidth().height(50.dp),
            ) {
                if (actions.loading) CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp) else Text(tr("Masuk"))
            }
            TextButton(onClick = { nav.navigate(Routes.REGISTER) { popUpTo(Routes.LOGIN) { inclusive = true } } }, modifier = Modifier.fillMaxWidth()) {
                Text(tr("Belum punya akun? Daftar"))
            }
        }
    }
}

@Composable
fun RegisterScreen(repo: KyreadRepository, nav: NavHostController) {
    val actions = rememberAuthActions(repo, nav)
    var name by rememberSaveable { mutableStateOf("") }
    var username by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }
    var confirm by rememberSaveable { mutableStateOf("") }

    AuthScaffold(nav, tr("Buat akun baru"), actions.error, actions.loading, onGoogle = actions::google) {
        Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
            OutlinedTextField(name, { name = it }, Modifier.fillMaxWidth(), label = { Text(tr("Nama lengkap")) }, singleLine = true)
            OutlinedTextField(
                username, { username = it.filter { c -> c.isLetterOrDigit() || c == '_' || c == '-' } }, Modifier.fillMaxWidth(),
                label = { Text(tr("Username")) }, singleLine = true, supportingText = { Text(tr("Huruf, angka, - dan _")) },
            )
            OutlinedTextField(
                email, { email = it }, Modifier.fillMaxWidth(), label = { Text(tr("Email")) }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            )
            PasswordField(password, { password = it }, tr("Password (min. 8, huruf & angka)"))
            PasswordField(confirm, { confirm = it }, tr("Ulangi password"), ImeAction.Done)
            Button(
                onClick = { actions.run { repo.register(name.trim(), username.trim(), email.trim(), password, confirm) } },
                enabled = !actions.loading && listOf(name, username, email, password, confirm).all { it.isNotBlank() },
                modifier = Modifier.fillMaxWidth().height(50.dp),
            ) {
                if (actions.loading) CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp) else Text(tr("Daftar"))
            }
            TextButton(onClick = { nav.navigate(Routes.LOGIN) { popUpTo(Routes.REGISTER) { inclusive = true } } }, modifier = Modifier.fillMaxWidth()) {
                Text(tr("Sudah punya akun? Masuk"))
            }
        }
    }
}
