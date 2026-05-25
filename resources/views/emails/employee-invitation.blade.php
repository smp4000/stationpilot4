<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einladung zur Dateneingabe – StationPilot</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #1e40af;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
        }
        .header p {
            color: #bfdbfe;
            margin: 6px 0 0;
            font-size: 14px;
        }
        .body {
            padding: 36px 40px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .body .name {
            font-weight: bold;
            font-size: 16px;
        }
        .button-wrap {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background-color: #1e40af;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .link-fallback {
            font-size: 13px;
            color: #6b7280;
            word-break: break-all;
            margin-top: 8px;
        }
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #1e40af;
            padding: 14px 18px;
            border-radius: 4px;
            margin: 24px 0;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #1e3a8a;
        }
        .expiry {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 4px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>StationPilot</h1>
            <p>Mitarbeiterverwaltung</p>
        </div>
        <div class="body">
            <p>Guten Tag, <span class="name">{{ $employee->first_name }} {{ $employee->last_name }}</span>,</p>

            <p>
                Sie wurden von <strong>{{ $employee->station->name ?? 'Ihrer Station' }}</strong>
                eingeladen, Ihre Mitarbeiterdaten im StationPilot-System zu vervollständigen.
            </p>

            <div class="info-box">
                <p>Bitte vervollständigen Sie Ihre Mitarbeiterdaten, indem Sie auf den folgenden Link klicken:</p>
            </div>

            <div class="button-wrap">
                <a href="{{ route('employee.invitation.show', $employee->invitation_token) }}" class="button">
                    Daten jetzt eingeben
                </a>
                <p class="link-fallback">
                    Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:<br>
                    {{ route('employee.invitation.show', $employee->invitation_token) }}
                </p>
            </div>

            <p>
                Folgende Informationen werden abgefragt: persönliche Stammdaten, Anschrift, Kontaktdaten,
                Bankverbindung sowie Ihre Sozialversicherungsnummer.
            </p>

            <p class="expiry">
                Dieser Einladungslink ist 7 Tage gültig und läuft am
                <strong>{{ $employee->invitation_expires_at?->format('d.m.Y') }}</strong> ab.
                Danach ist eine erneute Einladung erforderlich.
            </p>

            <p>
                Bei Fragen wenden Sie sich bitte direkt an Ihren Arbeitgeber.
            </p>

            <p>Mit freundlichen Grüßen,<br>Ihr StationPilot-Team</p>
        </div>
        <div class="footer">
            Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese Nachricht.
        </div>
    </div>
</body>
</html>
