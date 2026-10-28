# Flutter / plugins (release shrinker)
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-dontwarn io.flutter.embedding.**

-keepattributes *Annotation*,Signature,InnerClasses,EnclosingMethod

# local_auth / AndroidX Biometric
-keep class androidx.biometric.** { *; }

# workmanager
-keep class androidx.work.** { *; }
-keep class dev.fluttercommunity.workmanager.** { *; }

# flutter_local_notifications
-keep class com.dexterous.** { *; }

# secure storage
-keep class com.it_nomads.fluttersecurestorage.** { *; }

# Geolocator / Play Services (if merged)
-dontwarn com.google.android.gms.**
