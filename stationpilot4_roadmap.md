# Stationpilot 4 — Vollständige Roadmap
## Stand: Prompt 01 abgeschlossen

---

## Legende
- ✅ Abgeschlossen
- 🔄 In Arbeit
- ⬜ Offen
- 📱 Android-App
- 🌐 Web (Laravel + Filament)

---

## Stack

| Komponente | Version | Notiz |
|---|---|---|
| PHP | 8.2.12 | XAMPP Windows |
| Laravel | 12.x | |
| Filament | 5.6.3 | `^5.6` — v5.0.0 hatte Security-Advisory |
| MySQL | XAMPP | DB: `stationpilot4`, User: `root` |
| Panels | `/admin` + `/app` | Aral-Blau `#003B95` |

---

## 🌐 WEB-APP — Laravel + Filament

### Phase 1 — Fundament

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P01 | Laravel + Filament | Laravel 12 · Filament 5.6.3 · 2 Panels (`/admin` + `/app`) · Aral-Blau · MySQL · .env strukturiert | ✅ |
| P02 | Models + Migrations | User-Model · Tenant-Model · Migrationen · HasUlid Trait | ⬜ |
| P03 | Auth + Permissions | Spatie v6 Teams-Modus · 6 Rollen · Permissions-Set · Seeder | ⬜ |
| P04 | Multi-Tenancy | BelongsToTenant Trait · TenantScope · EnsureTenantContext Middleware | ⬜ |
| P05 | Admin Panel | TenantResource · UserResource · AuditLogResource (read-only) | ⬜ |
| P06 | DSGVO-Fundament | AuditLog Model · AuditService · Auth-Listener · verschlüsselte Felder | ⬜ |

### Phase 2 — Partner-Panel

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P07 | Station-Modul | StationResource (Tab-Formular) · Leaflet-Karte · OpenStreetMap | ⬜ |
| P08 | BenzinpreisService | PLZ-Suche · Daten-Import-Wizard · Queue-Job | ⬜ |
| P09 | Mitarbeiter-Modul | EmployeeResource · Personalakte · Onboarding-Status | ⬜ |
| P10 | Einladungssystem | Einladung per E-Mail · Device-Invite · scan_code generieren | ⬜ |

### Phase 3 — Billing

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P11 | Abo-System | SubscriptionTiers · Mandant-Registrierung · Trial-Workflow | ⬜ |
| P12 | SEPA-Mandat | sepa_mandates · Mandat-PDF · Unterschrift · Verwaltung | ⬜ |
| P13 | Rechnungs-Lauf | invoices · ZUGFeRD PDF · Pre-Notification · Mahnwesen 3-stufig | ⬜ |
| P14 | SEPA-XML-Export | pain.008 · sepa_collections · Super-Admin Export-UI | ⬜ |

### Phase 4 — Dokumente + HR

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P15 | Vertragsmanagement | employee_contracts · PDF-Templates · E-Signatur | ⬜ |
| P16 | DATEV LODAS Export | Lohndaten-Export · CSV-Format · Steuerberater-Zugang | ⬜ |
| P17 | Urlaubsanträge | leave_requests · Genehmigungsworkflow · Kalender | ⬜ |
| P18 | Löschkonzept | DSGVO-Jobs · ZIP-Export · Archivierung · Löschzertifikat | ⬜ |

### Phase 5 — API (Brücke zur Android-App)

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P19 | API Foundation | /api/v1/ Struktur · Sanctum · Device-Registrierung · ApiResponse-Format | ⬜ |
| P20 | API Auth | POST auth/login · POST auth/scan-login · Device-Token · Session-Token | ⬜ |
| P21 | API MHD | GET/POST/PUT/DELETE mhd · mhd/summary · Berechtigungen | ⬜ |
| P22 | API Schicht | shift-settlements komplett · Multipart (Fotos) · Münzrollen | ⬜ |
| P23 | API Kiosk | kiosk/articles · Lieferung · Remission · Inventur | ⬜ |
| P24 | API Tankbetrug + Gutscheine | fuel-thefts · vouchers · print/label | ⬜ |

### Phase 6 — Marketing + Go-Live

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| P25 | Marketing-Site | /, /funktionen, /preise, /kontakt · SEO · Sitemap · OpenGraph | ⬜ |
| P26 | Hetzner + Forge | Server-Setup · Nginx · SSL · Supervisor · Backup | ⬜ |

---

## 📱 ANDROID-APP — Kotlin + Jetpack Compose

### Phase A1 — Fundament

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| A01 | Projekt-Setup | Android Studio · Gradle · Packages · Verzeichnisstruktur | ⬜ |
| A02 | Design-System | Stationpilot-Theme · Aral-Blau · Material 3 · Fonts | ⬜ |
| A03 | API-Client | Retrofit · OkHttp · ApiResponse-Wrapper · RetrofitClient | ⬜ |
| A04 | DataStore | DevicePreferences · Device-Token · Auth-Token · Settings | ⬜ |

### Phase A2 — Auth + Navigation

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| A05 | Setup-Screen | QR-Code scannen · Device registrieren · Server-URL konfigurieren | ⬜ |
| A06 | Login-Screen | Mitarbeiterliste · PIN-Eingabe · NFC-Login · Scan-Code | ⬜ |
| A07 | Navigation | NavGraph · Drawer · HomeScreen · Routing | ⬜ |

### Phase A3 — Module

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| A08 | MHD-Kontrolle | Liste · Erstellen · Verlängern · Entsorgen · Scanner | ⬜ |
| A09 | Schichtabrechnung | Prüffragen · Münzrollen · Tresor · Abschluss · Signatur · Foto | ⬜ |
| A10 | Tankbetrug | Formular · Kennzeichen · Foto · Signatur · GPS | ⬜ |
| A11 | Kiosk | Lieferung · Remission · Inventur · EAN-Scanner · Duplikat-Erkennung | ⬜ |
| A12 | Artikel-Info | Barcode-Suche · Preise · Lager · EAN-Liste | ⬜ |
| A13 | Gutscheine | Lookup · Ausgabe · Einlösung · Gruppenprüfung | ⬜ |
| A14 | Meine Schichten | Liste · Details · Kommentar hinzufügen | ⬜ |

### Phase A4 — Hardware + Einstellungen

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| A15 | Scanner-Bridge | MDE Hardware-Wedge · ScanField · BarcodeScannerDialog (CameraX + ML Kit) | ⬜ |
| A16 | NFC-Helper | NFC-Tag lesen/schreiben · NFC-Login-Flow · Tag beschriften | ⬜ |
| A17 | Drucker (DYMO) | DymoClient · Label-Print · Etiketten-Format | ⬜ |
| A18 | Einstellungen | TabVerbindung · TabModule · TabSicherheit · TabMhd · TabAllgemein | ⬜ |

### Phase A5 — Go-Live

| # | Prompt | Inhalt | Status |
|---|---|---|---|
| A19 | Release-Build | ProGuard · Signing · APK/AAB · Versioning | ⬜ |
| A20 | Geräte-Deployment | Netum Q700 · Zebra · Tablet · MDM-Verteilung | ⬜ |

---

## Gesamtfortschritt

| Bereich | Gesamt | Fertig | Offen |
|---|---|---|---|
| Web (Laravel) | 26 Prompts | 1 | 25 |
| Android (Kotlin) | 20 Prompts | 0 | 20 |
| **Gesamt** | **46 Prompts** | **1** | **45** |

---

## Nächster Schritt

**→ Prompt 02: User-Model + Tenant-Model + Migrationen**

---

## Abgeschlossene Prompts — Notizen

### P01 — Laravel + Filament ✅
- Laravel 12 in `C:\xampp\htdocs\stationpilot4` installiert
- Filament v5.6.3 (v5.0.0 hatte Security-Advisory `PKSA-5bdf-2x61-v43c` in `filament/tables`)
- `AdminPanelProvider` (default, `/admin`) und `AppPanelProvider` (`/app`) manuell angelegt
- Sessions-Tabelle ist in Laravel 12 bereits in `0001_01_01_000000_create_users_table.php` enthalten
- `.env` in 6 Blöcke gegliedert: APP · DATENBANK · CACHE/SESSION/QUEUE · LOGGING · MAIL · SONSTIGES
- Filament-Verzeichnisse angelegt: `app/Filament/{Admin,App}/{Resources,Pages,Widgets}`

---

*Zuletzt aktualisiert nach: Prompt 01 ✅ — 2026-05-16*
