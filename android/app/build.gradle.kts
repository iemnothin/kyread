import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.kotlin.serialization)
}

val apiUrl = (project.findProperty("kyreadApiUrl") as String?) ?: "https://kyread-staging.skyvora.id/api/v1/"
// "Web client ID" OAuth Google (sama dengan GOOGLE_CLIENT_ID di server). Kosong = tombol Google disembunyikan.
val googleWebClientId = (project.findProperty("googleWebClientId") as String?).orEmpty()
// Build yang mengarah ke server staging diberi label "Staging" (nama launcher & badge di aplikasi).
val isStaging = apiUrl.contains("staging")

// Kredensial rilis dibaca dari android/keystore.properties (tidak di-commit).
val keystoreProps = Properties().apply {
    val file = rootProject.file("keystore.properties")
    if (file.exists()) file.inputStream().use { load(it) }
}

// Terjemahan dipakai bersama web: lang/*.json di root repo -> assets/i18n/*.json.
val i18nAssets = layout.buildDirectory.dir("generated/i18n")
val copyTranslations by tasks.registering(Sync::class) {
    from(rootProject.file("../lang")) { include("*.json") }
    into(i18nAssets.map { it.dir("i18n") })
}
// Animasi Lottie dipakai bersama web: resources/lottie/*.json -> assets/lottie/*.json.
val copyLottie by tasks.registering(Sync::class) {
    from(rootProject.file("../resources/lottie")) { include("*.json") }
    into(i18nAssets.map { it.dir("lottie") })
}
tasks.named("preBuild") { dependsOn(copyTranslations, copyLottie) }

android {
    namespace = "id.skyvora.kyread"
    compileSdk = 36

    defaultConfig {
        applicationId = "id.skyvora.kyread"
        minSdk = 26
        targetSdk = 36
        versionCode = 1
        versionName = "1.0.0"

        buildConfigField("String", "API_BASE_URL", "\"$apiUrl\"")
        buildConfigField("String", "GOOGLE_WEB_CLIENT_ID", "\"$googleWebClientId\"")
        buildConfigField("boolean", "IS_STAGING", "$isStaging")
        resValue("string", "app_name", if (isStaging) "kyread Staging" else "kyread")
        vectorDrawables { useSupportLibrary = true }
    }

    signingConfigs {
        if (keystoreProps.isNotEmpty()) {
            create("release") {
                storeFile = rootProject.file(keystoreProps.getProperty("storeFile"))
                storePassword = keystoreProps.getProperty("storePassword")
                keyAlias = keystoreProps.getProperty("keyAlias")
                keyPassword = keystoreProps.getProperty("keyPassword")
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            signingConfig = signingConfigs.findByName("release") ?: signingConfigs.getByName("debug")
        }
        debug {
            applicationIdSuffix = ".debug"
            versionNameSuffix = "-debug"
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    sourceSets["main"].assets.srcDir(i18nAssets)

    packaging {
        resources.excludes += "/META-INF/{AL2.0,LGPL2.1}"
    }
}

kotlin {
    compilerOptions {
        jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17)
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.splashscreen)
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.lifecycle.runtime.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.navigation.compose)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.material.icons)
    debugImplementation(libs.androidx.compose.ui.tooling)

    implementation(libs.androidx.datastore.preferences)
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging) // hanya aktif saat BuildConfig.DEBUG; dibuang R8 di rilis
    implementation(libs.kotlinx.serialization.json)
    implementation(libs.coil.compose)
    implementation(libs.androidx.credentials)
    implementation(libs.androidx.credentials.play)
    implementation(libs.googleid)
    implementation(libs.lottie.compose)
}
