package id.skyvora.kyread.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.unit.dp

/** Biru khas kyread (#43AAFF) dan versi lebih gelap untuk teks/tombol agar kontrasnya cukup. */
val KyBlue = Color(0xFF43AAFF)
val KyBlueDark = Color(0xFF1A73C8)
val KyStar = Color(0xFFF5B301)

private val LightColors = lightColorScheme(
    primary = KyBlueDark,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFD9ECFF),
    onPrimaryContainer = Color(0xFF00325A),
    secondary = KyBlue,
    onSecondary = Color.White,
    background = Color(0xFFF5F7FB),
    surface = Color.White,
    surfaceVariant = Color(0xFFEEF3F9),
    onSurfaceVariant = Color(0xFF5B6472),
    error = Color(0xFFC62828),
)

private val DarkColors = darkColorScheme(
    primary = KyBlue,
    onPrimary = Color(0xFF00325A),
    primaryContainer = Color(0xFF0B4A80),
    onPrimaryContainer = Color(0xFFD9ECFF),
    secondary = KyBlue,
    background = Color(0xFF0F141A),
    surface = Color(0xFF171D24),
    surfaceVariant = Color(0xFF222A33),
)

@Composable
fun KyreadTheme(darkTheme: Boolean = isSystemInDarkTheme(), content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkColors else LightColors,
        shapes = Shapes(
            small = RoundedCornerShape(8.dp),
            medium = RoundedCornerShape(14.dp),
            large = RoundedCornerShape(20.dp),
        ),
        content = content,
    )
}
