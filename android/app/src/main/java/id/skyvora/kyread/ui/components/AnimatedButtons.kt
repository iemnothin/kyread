package id.skyvora.kyread.ui.components

import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.RowScope
import androidx.compose.material3.ButtonColors
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.IconButtonColors
import androidx.compose.material3.IconButtonDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Shape
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.material3.Button as M3Button
import androidx.compose.material3.FilledTonalIconButton as M3FilledTonalIconButton
import androidx.compose.material3.IconButton as M3IconButton
import androidx.compose.material3.OutlinedButton as M3OutlinedButton
import androidx.compose.material3.TextButton as M3TextButton

/*
 * Pengganti tombol Material 3 dengan animasi tekan (mengecil lalu memantul kembali).
 * Signature-nya sama dengan versi Material, jadi layar cukup mengimpor dari paket ini.
 */

/** Interaksi + modifier skala yang dipakai bersama semua tombol. */
@Composable
fun rememberPressScale(pressedScale: Float = 0.94f): Pair<MutableInteractionSource, Modifier> {
    val source = remember { MutableInteractionSource() }
    val pressed by source.collectIsPressedAsState()
    val scale by animateFloatAsState(
        targetValue = if (pressed) pressedScale else 1f,
        animationSpec = spring(dampingRatio = Spring.DampingRatioMediumBouncy, stiffness = Spring.StiffnessMedium),
        label = "press-scale",
    )
    return source to Modifier.graphicsLayer { scaleX = scale; scaleY = scale }
}

@Composable
fun Button(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    shape: Shape = ButtonDefaults.shape,
    colors: ButtonColors = ButtonDefaults.buttonColors(),
    contentPadding: PaddingValues = ButtonDefaults.ContentPadding,
    content: @Composable RowScope.() -> Unit,
) {
    val (source, scale) = rememberPressScale()
    M3Button(onClick, modifier.then(scale), enabled, shape, colors, contentPadding = contentPadding, interactionSource = source, content = content)
}

@Composable
fun OutlinedButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    shape: Shape = ButtonDefaults.outlinedShape,
    colors: ButtonColors = ButtonDefaults.outlinedButtonColors(),
    border: BorderStroke? = ButtonDefaults.outlinedButtonBorder(enabled),
    contentPadding: PaddingValues = ButtonDefaults.ContentPadding,
    content: @Composable RowScope.() -> Unit,
) {
    val (source, scale) = rememberPressScale()
    M3OutlinedButton(
        onClick, modifier.then(scale), enabled, shape, colors,
        border = border, contentPadding = contentPadding, interactionSource = source, content = content,
    )
}

@Composable
fun TextButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    shape: Shape = ButtonDefaults.textShape,
    colors: ButtonColors = ButtonDefaults.textButtonColors(),
    contentPadding: PaddingValues = ButtonDefaults.TextButtonContentPadding,
    content: @Composable RowScope.() -> Unit,
) {
    val (source, scale) = rememberPressScale()
    M3TextButton(onClick, modifier.then(scale), enabled, shape, colors, contentPadding = contentPadding, interactionSource = source, content = content)
}

@Composable
fun IconButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    colors: IconButtonColors = IconButtonDefaults.iconButtonColors(),
    content: @Composable () -> Unit,
) {
    val (source, scale) = rememberPressScale(0.85f)
    M3IconButton(onClick, modifier.then(scale), enabled, colors, interactionSource = source, content = content)
}

@Composable
fun FilledTonalIconButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    colors: IconButtonColors = IconButtonDefaults.filledTonalIconButtonColors(),
    content: @Composable () -> Unit,
) {
    val (source, scale) = rememberPressScale(0.85f)
    M3FilledTonalIconButton(onClick, modifier.then(scale), enabled, colors = colors, interactionSource = source, content = content)
}
