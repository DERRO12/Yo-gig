# Yo-gig House Manager app

Flutter app for clients, maids and the super admin. It uses the Laravel site in the parent folder as its backend (`/api/v1`), so every service, page block, setting and booking is the same one the website shows.

## Run it

1. In the Laravel folder: `php artisan migrate` and `php artisan serve` (port 8000).
2. Here: `flutter pub get`, then `flutter run`.

The app talks to `http://10.0.2.2:8000/api/v1` on the Android emulator and `http://localhost:8000/api/v1` elsewhere. For a phone or a live server, either build with
`--dart-define=API_BASE_URL=https://your-domain/api/v1` or tap the server icon on the sign-in screen.

## Build

- Android: `flutter build apk --release` (needs a JDK; set `JAVA_HOME`)
- Web: `flutter build web --release`
