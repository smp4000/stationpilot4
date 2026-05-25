<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Umbenennung stations → gas_stations und vollständige Erweiterung des Schemas.
 *
 * Blöcke:
 *   A) Tabelle umbenennen
 *   B) Spalten umbenennen
 *   C) Alte Spalten entfernen
 *   D) brand_id FK ergänzen
 *   E) Allgemein-Felder
 *   F) Adresse + Kontakt (Erweiterungen)
 *   G) Geschäftsdaten
 *   H) Ausstattung (erweitert)
 *   I) Shop-Felder
 *   J) Medien & Wettbewerb & Preise
 *   K) Sonstiges
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── A) Tabelle umbenennen ────────────────────────────────────────────
        Schema::rename('stations', 'gas_stations');

        Schema::table('gas_stations', function (Blueprint $table) {

            // ── B) Spalten umbenennen ────────────────────────────────────────
            $table->renameColumn('lat',             'latitude');
            $table->renameColumn('lng',             'longitude');
            $table->renameColumn('opening_date_ok', 'first_opening_ok');
            $table->renameColumn('opening_date_dk', 'first_opening_dk');
        });

        Schema::table('gas_stations', function (Blueprint $table) {

            // ── C) Alte Spalten entfernen ────────────────────────────────────
            // brand (string) → brand_id (FK, folgt gleich)
            $table->dropColumn('brand');
            // Bankdaten → separate Tabelle gas_station_bank_accounts
            $table->dropColumn(['bank_name', 'account_holder']);
            // Ausstattung alt → neue Felder
            $table->dropColumn(['tank_count', 'dispenser_count', 'wash_model']);
            // has_bistro → in additional_businesses JSON
            $table->dropColumn('has_bistro');
            // meta → settings JSON (neu)
            $table->dropColumn('meta');
        });

        // iban/bic sind encrypted text – separat droppen (nach dem Batch oben)
        Schema::table('gas_stations', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bic']);
        });

        Schema::table('gas_stations', function (Blueprint $table) {

            // ── D) brand_id FK ───────────────────────────────────────────────
            $table->foreignId('brand_id')
                ->nullable()
                ->after('name')
                ->constrained('brands')
                ->nullOnDelete();

            // ── E) Allgemein ─────────────────────────────────────────────────
            $table->string('sales_channel', 100)->nullable()->after('station_number')
                ->comment('Vertriebskanal');
            $table->string('ownership_type', 10)->nullable()->after('sales_channel')
                ->comment('DOFO | COFO | DODO | CODO | COCO');
            $table->string('district', 100)->nullable()->after('ownership_type')
                ->comment('Distrikt');
            $table->string('district_description')->nullable()->after('district')
                ->comment('Distrikt-Beschreibung');
            $table->string('region', 100)->nullable()->after('district_description')
                ->comment('Bezirk');
            $table->string('region_manager')->nullable()->after('region')
                ->comment('Bezirksleitung (Freitext)');
            $table->string('station_number_fuel', 50)->nullable()->after('region_manager')
                ->comment('Tst.-Nr. Kraftstoff');
            $table->string('station_number_shop', 50)->nullable()->after('station_number_fuel')
                ->comment('Tst.-Nr. Shop');
            $table->boolean('has_toll_terminal')->default(false)->after('station_number_shop')
                ->comment('Mautstellenterminal vorhanden');

            // ── F) Adresse & Kontakt ─────────────────────────────────────────
            $table->string('district_part', 100)->nullable()->after('city')
                ->comment('Ortsteil');
            $table->string('state', 5)->nullable()->after('district_part')
                ->comment('Bundesland (ISO 3166-2:DE Kürzel, z. B. BY)');
            $table->string('phone', 30)->nullable()->after('country')
                ->comment('Telefon');
            $table->string('fax', 30)->nullable()->after('phone')
                ->comment('Fax');
            $table->string('email')->nullable()->after('fax')
                ->comment('E-Mail');
            $table->string('website')->nullable()->after('email')
                ->comment('Webseite');
            $table->string('academic_title', 20)->nullable()->after('website')
                ->comment('Akad. Grad');
            $table->string('contact_first_name')->nullable()->after('academic_title')
                ->comment('Ansprechpartner Vorname');
            $table->string('contact_last_name')->nullable()->after('contact_first_name')
                ->comment('Ansprechpartner Nachname');

            // ── G) Geschäftsdaten ────────────────────────────────────────────
            $table->string('tax_id', 50)->nullable()->after('contact_last_name')
                ->comment('Steuernummer / USt-IdNr.');
            $table->string('trade_register', 100)->nullable()->after('tax_id')
                ->comment('Handelsregisternummer');

            // ── H) Ausstattung (erweitert) ───────────────────────────────────
            $table->unsignedSmallInteger('num_pumps')->nullable()->after('has_shop')
                ->comment('Anzahl Zapfsäulen');
            $table->boolean('has_camera')->default(false)->after('num_pumps')
                ->comment('Videoüberwachung vorhanden');
            $table->json('fuel_types')->nullable()->after('has_car_wash')
                ->comment('Kraftstoffarten (JSON-Array: super, e10, diesel, adblue, …)');
            $table->json('services')->nullable()->after('fuel_types')
                ->comment('Dienstleistungen (JSON-Array: luft, staubsauger, lkw, …)');
            $table->json('additional_businesses')->nullable()->after('services')
                ->comment('Nebengeschäfte (JSON-Array: bistro, lotto, paket, …)');
            $table->json('car_wash_details')->nullable()->after('additional_businesses')
                ->comment('Waschanlagen-Details (JSON)');

            // ── I) Shop ──────────────────────────────────────────────────────
            $table->string('shop_size', 50)->nullable()->after('car_wash_details')
                ->comment('Shopgröße (z. B. S, M, L, XL oder m²)');
            $table->string('shop_type', 50)->nullable()->after('shop_size')
                ->comment('Shoptyp (convenience, kiosk, bistro, full)');
            $table->string('shop_class', 10)->nullable()->after('shop_type')
                ->comment('Shop-Klasse (A, B, C)');
            $table->date('shop_setup_date')->nullable()->after('shop_class')
                ->comment('Einrichtungsdatum Shop');
            $table->string('nielsen_area', 10)->nullable()->after('shop_setup_date')
                ->comment('Nielsen-Gebiet');
            $table->string('price_region', 50)->nullable()->after('nielsen_area')
                ->comment('Preisregion');
            $table->string('assortment_level', 50)->nullable()->after('price_region')
                ->comment('Sortimentsstufe');
            $table->string('shop_partner')->nullable()->after('assortment_level')
                ->comment('Shop-Partner');
            $table->string('shop_operation_number', 50)->nullable()->after('shop_partner')
                ->comment('Shop-Betriebsnummer');

            // ── J) Medien, Wettbewerb, Preise ───────────────────────────────
            $table->string('logo_path')->nullable()->after('shop_operation_number')
                ->comment('Pfad zum Stations-Logo');
            $table->json('photos')->nullable()->after('logo_path')
                ->comment('Fotos (JSON-Array mit Pfad + Label)');
            $table->json('competitors')->nullable()->after('photos')
                ->comment('Wettbewerber (JSON: name, street, distance_m, notes)');
            $table->decimal('price_super', 5, 3)->nullable()->after('competitors')
                ->comment('Preis Super in €');
            $table->decimal('price_e10', 5, 3)->nullable()->after('price_super')
                ->comment('Preis E10 in €');
            $table->decimal('price_diesel', 5, 3)->nullable()->after('price_e10')
                ->comment('Preis Diesel in €');
            $table->timestamp('prices_updated_at')->nullable()->after('price_diesel')
                ->comment('Zeitpunkt letzter Preisabruf');

            // ── K) Sonstiges ─────────────────────────────────────────────────
            $table->text('notes')->nullable()->after('prices_updated_at')
                ->comment('Interne Notizen');
            $table->json('settings')->nullable()->after('notes')
                ->comment('Stationsspezifische Einstellungen (JSON)');
        });
    }

    public function down(): void
    {
        // Neue Felder entfernen und Umbenennung rückgängig machen
        Schema::table('gas_stations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn([
                'sales_channel', 'ownership_type', 'district', 'district_description',
                'region', 'region_manager', 'station_number_fuel', 'station_number_shop', 'has_toll_terminal',
                'district_part', 'state', 'phone', 'fax', 'email', 'website',
                'academic_title', 'contact_first_name', 'contact_last_name',
                'tax_id', 'trade_register',
                'num_pumps', 'has_camera', 'fuel_types', 'services', 'additional_businesses', 'car_wash_details',
                'shop_size', 'shop_type', 'shop_class', 'shop_setup_date', 'nielsen_area',
                'price_region', 'assortment_level', 'shop_partner', 'shop_operation_number',
                'logo_path', 'photos', 'competitors',
                'price_super', 'price_e10', 'price_diesel', 'prices_updated_at',
                'notes', 'settings',
            ]);
            $table->renameColumn('latitude',       'lat');
            $table->renameColumn('longitude',      'lng');
            $table->renameColumn('first_opening_ok', 'opening_date_ok');
            $table->renameColumn('first_opening_dk', 'opening_date_dk');

            // Alte Felder wiederherstellen
            $table->string('brand', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->text('iban')->nullable();
            $table->text('bic')->nullable();
            $table->string('account_holder')->nullable();
            $table->smallInteger('tank_count')->nullable();
            $table->smallInteger('dispenser_count')->nullable();
            $table->string('wash_model')->nullable();
            $table->boolean('has_bistro')->default(false);
            $table->json('meta')->nullable();
        });

        Schema::rename('gas_stations', 'stations');
    }
};
