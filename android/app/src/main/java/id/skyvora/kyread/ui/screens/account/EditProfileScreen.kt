package id.skyvora.kyread.ui.screens.account

import id.skyvora.kyread.i18n.tr
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import id.skyvora.kyread.ui.components.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import id.skyvora.kyread.data.KyreadRepository
import id.skyvora.kyread.data.ProfileRequest
import id.skyvora.kyread.ui.components.BackIcon
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditProfileScreen(repo: KyreadRepository, nav: NavHostController) {
    val user = repo.user.value ?: run { nav.popBackStack(); return }
    val snackbar = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()
    var saving by remember { mutableStateOf(false) }

    var name by rememberSaveable { mutableStateOf(user.name) }
    var nickname by rememberSaveable { mutableStateOf(user.nickname.orEmpty()) }
    var username by rememberSaveable { mutableStateOf(user.username) }
    var email by rememberSaveable { mutableStateOf(user.email) }
    var phone by rememberSaveable { mutableStateOf(user.phone.orEmpty()) }
    var about by rememberSaveable { mutableStateOf(user.about.orEmpty()) }
    var street by rememberSaveable { mutableStateOf(user.address.street.orEmpty()) }
    var city by rememberSaveable { mutableStateOf(user.address.city.orEmpty()) }
    var province by rememberSaveable { mutableStateOf(user.address.province.orEmpty()) }
    var zipcode by rememberSaveable { mutableStateOf(user.address.zipcode.orEmpty()) }

    Scaffold(
        topBar = { TopAppBar(title = { Text(tr("Edit Profil")) }, navigationIcon = { BackIcon { nav.popBackStack() } }) },
        snackbarHost = { SnackbarHost(snackbar) },
    ) { padding ->
        Column(
            Modifier.padding(padding).imePadding().verticalScroll(rememberScrollState()).padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(tr("Data Diri"), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Field(name, { name = it }, tr("Nama lengkap"))
            Field(nickname, { nickname = it }, tr("Nama panggilan"))
            Field(username, { username = it }, tr("Username"))
            Field(email, { email = it }, tr("Email"), KeyboardType.Email)
            Field(phone, { phone = it }, tr("No. HP"), KeyboardType.Phone)
            Field(about, { about = it }, tr("Bio singkat"))

            Text(tr("Alamat Utama"), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 8.dp))
            OutlinedTextField(street, { street = it }, Modifier.fillMaxWidth(), label = { Text(tr("Alamat lengkap")) }, minLines = 2)
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(city, { city = it }, Modifier.weight(1f), label = { Text(tr("Kota")) }, singleLine = true)
                OutlinedTextField(province, { province = it }, Modifier.weight(1f), label = { Text(tr("Provinsi")) }, singleLine = true)
            }
            Field(zipcode, { if (it.length <= 5) zipcode = it.filter(Char::isDigit) }, tr("Kode pos"), KeyboardType.Number)

            Button(
                enabled = !saving,
                modifier = Modifier.fillMaxWidth().height(50.dp),
                onClick = {
                    scope.launch {
                        saving = true
                        runCatching {
                            repo.updateProfile(
                                ProfileRequest(
                                    name = name.trim(),
                                    nickname = nickname.trim().ifBlank { null },
                                    username = username.trim(),
                                    email = email.trim(),
                                    phone = phone.trim().ifBlank { null },
                                    about = about.trim().ifBlank { null },
                                    street = street.trim().ifBlank { null },
                                    city = city.trim().ifBlank { null },
                                    province = province.trim().ifBlank { null },
                                    zipcode = zipcode.trim().ifBlank { null },
                                ),
                            )
                        }.onSuccess { snackbar.showSnackbar(tr("Profil berhasil diperbarui.")) }
                            .onFailure { snackbar.showSnackbar(it.message ?: tr("Gagal menyimpan")) }
                        saving = false
                    }
                },
            ) {
                if (saving) CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp) else Text(tr("Simpan"))
            }
        }
    }
}

@Composable
private fun Field(value: String, onChange: (String) -> Unit, label: String, type: KeyboardType = KeyboardType.Text) {
    OutlinedTextField(
        value, onChange, Modifier.fillMaxWidth(),
        label = { Text(label) }, singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = type),
    )
}
