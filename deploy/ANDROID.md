# Android app (Capacitor)

The mobile web UI uses a bottom tab bar and app-style layout on screens under 1024px. The same build can be wrapped as a native Android app with [Capacitor](https://capacitorjs.com/).

## Prerequisites

- **Node.js** 20+
- **Android Studio** (latest stable) with Android SDK
- **JDK 21** (required by current Capacitor Android Gradle; e.g. Microsoft OpenJDK 21)

## One-time setup

From the repo root:

```bash
cd frontend
npm install
npx cap add android
```

If `android/` already exists, skip `cap add android`.

## Build APK (install on phone)

**Ready-made debug APK** (after a successful build):

`releases/TheMinimark-debug.apk` — copy to your phone and open to install (enable “Install unknown apps” if prompted).

To rebuild:

```bash
cd frontend
# Requires JDK 21 + Android SDK (ANDROID_HOME set)
set JAVA_HOME=C:\Program Files\Microsoft\jdk-21.0.11.10-hotspot
npm run android:sync
cd android
gradlew.bat assembleDebug
```

Output: `frontend/android/app/build/outputs/apk/debug/app-debug.apk`

From repo root: `npm run android:apk` (same steps; ensure `JAVA_HOME` points to JDK 21).

## Open in Android Studio

```bash
cd frontend
npm run android:sync
npm run android:open
```

In Android Studio: **Run** on a device or emulator.

### What the scripts do

| Script | Purpose |
|--------|---------|
| `npm run build:capacitor` | Production build with `VITE_API_BASE_URL=https://theminimark.com/api` |
| `npm run android:sync` | Build + copy web assets into the Android project |
| `npm run android:apk` | Sync + `assembleDebug` (debug APK) |
| `npm run android:open` | Open the project in Android Studio |

The Capacitor build talks to your live Hostinger API at `https://theminimark.com/api`. Change `frontend/.env.capacitor` if the API URL changes.

## Install on a phone (web, no store)

1. Deploy the latest `frontend/dist/` to Hostinger.
2. On Android Chrome, open **https://theminimark.com**
3. Menu → **Add to Home screen** / **Install app**

The PWA manifest (`manifest.webmanifest`) enables full-screen, standalone mode with the bottom navigation.

## Play Store release (later)

1. Generate a signed release APK/AAB in Android Studio (**Build → Generate Signed Bundle**).
2. Create a [Google Play Console](https://play.google.com/console) listing.
3. Upload the AAB, complete store assets, and submit for review.

Update `appId` in `frontend/capacitor.config.ts` before publishing if you need a different package name.

## Mobile app UI (web)

On phones and tablets under 1024px width:

- Fixed **bottom tab bar**: Home, Shop, Cart, Wishlist, Account
- Compact **top header** with logo + search (cart/wishlist moved to tabs)
- **Cart** opens as a bottom sheet
- Website **footer hidden** on mobile
- **Checkout / sign-in** screens hide the tab bar for a focused flow

Test locally: resize the browser or use Chrome DevTools device mode, then `npm run dev` in `frontend`.
