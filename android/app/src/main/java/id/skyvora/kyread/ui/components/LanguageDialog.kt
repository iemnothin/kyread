package id.skyvora.kyread.ui.components

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.unit.dp
import id.skyvora.kyread.i18n.I18n
import id.skyvora.kyread.i18n.tr

/** Pilihan bahasa antarmuka (dipakai di onboarding dan layar Akun). */
@Composable
fun LanguageDialog(onDismiss: () -> Unit, onPick: (String) -> Unit) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(tr("Bahasa")) },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState())) {
                I18n.languages.forEach { lang ->
                    val selected = lang.code == I18n.language.code
                    Row(
                        Modifier
                            .fillMaxWidth()
                            .selectable(selected = selected, onClick = { onPick(lang.code) }, role = Role.RadioButton)
                            .padding(vertical = 10.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        RadioButton(selected = selected, onClick = null)
                        Spacer(Modifier.width(12.dp))
                        Text(lang.native, modifier = Modifier.weight(1f))
                        Text(lang.code.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
            }
        },
        confirmButton = { TextButton(onClick = onDismiss) { Text(tr("Tutup")) } },
    )
}
