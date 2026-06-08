<?php

namespace App\Support;

/**
 * Zentraler Katalog aller Mandanten-Berechtigungen, getrennt nach Einsatzbereich.
 *
 *   Web-App (Filament /app)   → Permission-Präfix "partner.*"   → scope "web"
 *   GoPilot-App (Android/MDE)  → Permission-Präfix "employee.*"  → scope "gopilot"
 *
 * Single Source of Truth für:
 *   - RolesAndPermissionsSeeder (Permission-Registrierung + Standardrollen)
 *   - RollenResource (Web-Rollen) und GoPilotRollenResource (GoPilot-Rollen)
 *   - SyncTenantRoles Command
 */
class RolePermissions
{
    public const SCOPE_WEB     = 'web';
    public const SCOPE_GOPILOT = 'gopilot';

    /** Eingebaute Web-Rollen (nicht umbenennbar/löschbar). */
    public const BUILTIN_WEB_ROLES = [
        'partner_owner', 'partner_manager', 'station_manager', 'employee', 'tax_advisor',
    ];

    /** Eingebaute GoPilot-Rollen (nicht umbenennbar/löschbar). Keine – Partner legen eigene an. */
    public const BUILTIN_GOPILOT_ROLES = [];

    // ── Web-App Permission-Gruppen (partner.*) ───────────────────────────────

    public static function webGroups(): array
    {
        return [
            'perms_dashboard' => [
                'label' => '🏠 Dashboard',
                'perms' => [
                    'partner.dashboard.view' => 'Dashboard anzeigen',
                ],
            ],
            'perms_stations' => [
                'label' => '⛽ Tankstellen',
                'perms' => [
                    'partner.stations.list'   => 'Liste anzeigen',
                    'partner.stations.view'   => 'Details einsehen',
                    'partner.stations.create' => 'Anlegen',
                    'partner.stations.edit'   => 'Bearbeiten',
                    'partner.stations.delete' => 'Löschen',
                ],
            ],
            'perms_employees' => [
                'label' => '👥 Personal',
                'perms' => [
                    'partner.employees.list'      => 'Liste anzeigen',
                    'partner.employees.view'      => 'Details einsehen',
                    'partner.employees.create'    => 'Anlegen',
                    'partner.employees.edit'      => 'Bearbeiten',
                    'partner.employees.delete'    => 'Löschen',
                    'partner.employees.invite'    => 'Einladen',
                    'partner.employees.approve'   => 'Genehmigen',
                    'partner.employees.terminate' => 'Kündigen',
                ],
            ],
            'perms_contracts' => [
                'label' => '📄 Arbeitsverträge',
                'perms' => [
                    'partner.contracts.list'   => 'Liste anzeigen',
                    'partner.contracts.view'   => 'Details einsehen',
                    'partner.contracts.create' => 'Erstellen',
                    'partner.contracts.edit'   => 'Bearbeiten',
                    'partner.contracts.delete' => 'Löschen',
                    'partner.contracts.send'   => 'Versenden (Onboarding)',
                ],
            ],
            'perms_documents' => [
                'label' => '📋 Generierte Dokumente',
                'perms' => [
                    'partner.documents.list'   => 'Liste anzeigen',
                    'partner.documents.view'   => 'Details einsehen',
                    'partner.documents.create' => 'Generieren / Senden',
                    'partner.documents.delete' => 'Löschen',
                ],
            ],
            'perms_document_templates' => [
                'label' => '📝 Dokument-Vorlagen',
                'perms' => [
                    'partner.document_templates.list'   => 'Liste anzeigen',
                    'partner.document_templates.create' => 'Erstellen',
                    'partner.document_templates.edit'   => 'Bearbeiten',
                    'partner.document_templates.delete' => 'Löschen',
                ],
            ],
            'perms_keys' => [
                'label' => '🔑 Schlüssel',
                'perms' => [
                    'partner.keys.list'   => 'Liste anzeigen',
                    'partner.keys.view'   => 'Details einsehen',
                    'partner.keys.create' => 'Ausgeben',
                    'partner.keys.edit'   => 'Bearbeiten',
                    'partner.keys.delete' => 'Löschen',
                ],
            ],
            'perms_billing' => [
                'label' => '💳 Abrechnung',
                'perms' => [
                    'partner.billing.view'   => 'Einsehen',
                    'partner.billing.manage' => 'Verwalten',
                ],
            ],
            'perms_reports' => [
                'label' => '📊 Berichte',
                'perms' => [
                    'partner.reports.view'   => 'Anzeigen',
                    'partner.reports.export' => 'Exportieren',
                ],
            ],
            'perms_settings' => [
                'label' => '⚙️ Einstellungen & Team',
                'perms' => [
                    'partner.settings.view' => 'Einstellungen einsehen',
                    'partner.settings.edit' => 'Bearbeiten & Team-Verwaltung',
                ],
            ],
        ];
    }

    // ── GoPilot-App Permission-Gruppen (employee.*) ──────────────────────────

    public static function gopilotGroups(): array
    {
        return [
            'perms_gopilot_bistro' => [
                'label' => '🍽️ Bistro',
                'perms' => [
                    'employee.bistro.view'     => 'Bistro-Bereich sichtbar',
                    'employee.bistro.orders'   => 'Bestellungen',
                    'employee.bistro.daily'    => 'Tagesabschluss',
                    'employee.bistro.delivery' => 'Wareneingang',
                ],
            ],
            'perms_gopilot_shop' => [
                'label' => '🏪 Shop',
                'perms' => [
                    'employee.shop.view'      => 'Shop-Bereich sichtbar',
                    'employee.shop.cashier'   => 'Kassenabschluss',
                    'employee.shop.delivery'  => 'Wareneingang',
                    'employee.shop.inventory' => 'Inventur',
                ],
            ],
            'perms_gopilot_station' => [
                'label' => '⛽ Tankstelle',
                'perms' => [
                    'employee.station.view'     => 'Tankstellen-Bereich sichtbar',
                    'employee.station.shift'    => 'Schichtprotokoll führen',
                    'employee.station.tank'     => 'Tankkontrolle durchführen',
                    'employee.station.incident' => 'Störungen melden',
                ],
            ],
            'perms_gopilot_keys' => [
                'label' => '🔑 Schlüssel & Zugang',
                'perms' => [
                    'employee.keys.view'     => 'Schlüssel-Übergabe sichtbar',
                    'employee.keys.handover' => 'Schlüssel übergeben / zurücknehmen',
                ],
            ],
        ];
    }

    // ── Flache Permission-Listen ─────────────────────────────────────────────

    /** Alle Web-Permissions (partner.*) als flache Liste. */
    public static function webPermissions(): array
    {
        return self::flatten(self::webGroups());
    }

    /** Alle GoPilot-Permissions (employee.*) als flache Liste. */
    public static function gopilotPermissions(): array
    {
        return self::flatten(self::gopilotGroups());
    }

    /** Alle Permissions (web + gopilot) – zum globalen Registrieren. */
    public static function all(): array
    {
        return array_merge(self::webPermissions(), self::gopilotPermissions());
    }

    private static function flatten(array $groups): array
    {
        $out = [];
        foreach ($groups as $group) {
            $out = array_merge($out, array_keys($group['perms']));
        }
        return array_values(array_unique($out));
    }

    // ── Standard-Rollen pro Mandant ──────────────────────────────────────────

    /**
     * Eingebaute Web-Rollen und ihre Permissions (nur partner.*).
     * Reihenfolge = Anzeigereihenfolge.
     */
    public static function webStandardRoles(): array
    {
        $all = self::webPermissions();

        return [
            // Inhaber — voller Web-Zugriff
            'partner_owner' => $all,

            // Manager — wie Owner, aber kein Billing
            'partner_manager' => array_values(array_filter(
                $all, fn ($p) => ! str_starts_with($p, 'partner.billing')
            )),

            // Stationsleiter — Stationen/Mitarbeiter/Verträge/Schlüssel/MDE, kein Billing/Settings
            'station_manager' => [
                'partner.dashboard.view',
                'partner.stations.list', 'partner.stations.view',
                'partner.employees.list', 'partner.employees.view', 'partner.employees.invite',
                'partner.contracts.list', 'partner.contracts.view',
                'partner.documents.list', 'partner.documents.view',
                'partner.keys.list', 'partner.keys.view', 'partner.keys.create',
                'partner.reports.view',
            ],

            // Mitarbeiter — Dashboard + Stationen ansehen (Web-Self-Service)
            'employee' => [
                'partner.dashboard.view',
                'partner.stations.list', 'partner.stations.view',
            ],

            // Steuerberater — Personal + Verträge lesen, Berichte exportieren
            'tax_advisor' => [
                'partner.dashboard.view',
                'partner.employees.list', 'partner.employees.view',
                'partner.contracts.list', 'partner.contracts.view',
                'partner.documents.list', 'partner.documents.view',
                'partner.reports.view', 'partner.reports.export',
            ],
        ];
    }

    /**
     * Eingebaute GoPilot-Rollen. Keine Standardrollen mehr –
     * Partner legen GoPilot-Rollen selbst unter Einstellungen → GoPilot-Rollen an.
     */
    public static function gopilotStandardRoles(): array
    {
        return [];
    }

    // ── Anzeige-Labels für eingebaute Rollen ─────────────────────────────────

    public static function roleLabel(string $name): string
    {
        return match ($name) {
            'partner_owner'         => '👑 Inhaber',
            'partner_manager'       => '🏢 Manager',
            'station_manager'       => '🏪 Stationsleiter',
            'employee'              => '👤 Mitarbeiter',
            'tax_advisor'           => '📊 Steuerberater',
            'gopilot_schichtleiter' => '🧑‍✈️ Schichtleiter',
            'gopilot_tankwart'      => '⛽ Tankwart',
            'gopilot_kassierer'     => '🛒 Kassierer',
            'gopilot_bistro'        => '🍽️ Bistro-Kraft',
            default                 => $name,
        };
    }
}
