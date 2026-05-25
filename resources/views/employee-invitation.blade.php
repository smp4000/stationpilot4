<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitarbeiterdaten – StationPilot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --sp-blue:      #1e3a8a;
            --sp-blue-mid:  #2563eb;
            --sp-blue-light:#dbeafe;
        }
        body {
            background: linear-gradient(160deg, #e8f0fe 0%, #f1f5f9 60%);
            min-height: 100vh;
        }

        /* ── Karte ──────────────────────────────────────────────── */
        .sp-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(30,58,138,.12);
            overflow: hidden;
        }
        .sp-header {
            background: linear-gradient(135deg, var(--sp-blue) 0%, var(--sp-blue-mid) 100%);
            padding: 36px 40px 32px;
            position: relative;
        }
        .sp-header::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 24px;
            background: #fff;
            border-radius: 24px 24px 0 0;
        }
        .sp-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.15);
            border-radius: 8px;
            padding: 6px 14px;
            margin-bottom: 20px;
        }
        .sp-logo span { color: #fff; font-weight: 700; font-size: 1rem; letter-spacing: .5px; }
        .sp-header h1 { color: #fff; font-size: 1.6rem; font-weight: 700; margin: 0 0 4px; }
        .sp-header p  { color: #bfdbfe; margin: 0; font-size: .9rem; }

        /* ── Abschnitte ─────────────────────────────────────────── */
        .section-label {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--sp-blue-mid);
            border-bottom: 2px solid var(--sp-blue-light);
            padding-bottom: 6px;
            margin: 28px 0 16px;
        }
        .section-label:first-child { margin-top: 0; }

        /* ── Formular-Labels ────────────────────────────────────── */
        .form-label {
            font-size: .85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            padding: 10px 14px;
            font-size: .9rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--sp-blue-mid);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .form-text { font-size: .78rem; color: #6b7280; }

        /* ── IBAN-Status ────────────────────────────────────────── */
        #iban-status { min-height: 22px; }
        .iban-ok  { color: #16a34a; font-size: .82rem; font-weight: 600; }
        .iban-err { color: #dc2626; font-size: .82rem; font-weight: 600; }
        .iban-loading { color: #6b7280; font-size: .82rem; }
        #bic, #bank_name {
            background-color: #f9fafb;
            transition: background-color .3s;
        }
        #bic.autofilled, #bank_name.autofilled {
            background-color: #ecfdf5;
            border-color: #86efac;
        }

        /* ── Opt. Badge ─────────────────────────────────────────── */
        .badge-optional {
            font-size: .65rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 4px;
            padding: 2px 6px;
            margin-left: 6px;
            vertical-align: middle;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ── Submit-Button ──────────────────────────────────────── */
        .btn-sp {
            background: linear-gradient(135deg, var(--sp-blue) 0%, var(--sp-blue-mid) 100%);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            padding: 12px 40px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37,99,235,.35);
            transition: opacity .15s, transform .1s;
        }
        .btn-sp:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
        .btn-sp:active { transform: translateY(0); }

        /* ── Status-Seiten ──────────────────────────────────────── */
        .status-icon { font-size: 3.5rem; }
        .footer-note { font-size: .78rem; color: #94a3b8; }
    </style>
</head>
<body>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-xl-7 col-lg-8 col-md-10">

    {{-- ── Karte ──────────────────────────────────────────────────── --}}
    <div class="sp-card bg-white">

        {{-- Header --}}
        <div class="sp-header">
            <div class="sp-logo">
                <span>⛽ StationPilot</span>
            </div>
            <h1>Mitarbeiterdaten</h1>
            <p>{{ $employee->station->name ?? 'Ihre Tankstelle' }}</p>
        </div>

        {{-- Body --}}
        <div class="card-body p-4 pt-3">

            @if ($used)
            {{-- ── Erfolgreich übermittelt ──────────────────────── --}}
            <div class="text-center py-5">
                <div class="status-icon mb-3">🎉</div>
                <h4 class="fw-bold text-success mb-2">Vielen Dank, {{ $employee->first_name }}!</h4>
                <p class="text-muted">Ihre Mitarbeiterdaten wurden erfolgreich übermittelt.<br>
                Ihr Arbeitgeber hat nun Zugriff auf Ihre Angaben.</p>
                <div class="mt-4 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <p class="mb-0 text-success" style="font-size:.9rem;">✔ Formular abgeschlossen · Kein weiterer Handlungsbedarf</p>
                </div>
            </div>

            @elseif ($expired)
            {{-- ── Link abgelaufen ──────────────────────────────── --}}
            <div class="text-center py-5">
                <div class="status-icon mb-3">⏰</div>
                <h4 class="fw-bold text-danger mb-2">Einladungslink abgelaufen</h4>
                <p class="text-muted">Dieser Link ist leider nicht mehr gültig.<br>
                Bitte wenden Sie sich an Ihren Arbeitgeber, um eine neue Einladung zu erhalten.</p>
            </div>

            @else
            {{-- ── Formular ─────────────────────────────────────── --}}
            <p class="mb-0 mt-1">Guten Tag, <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>!</p>
            <p class="text-muted mb-3" style="font-size:.9rem;">
                Bitte vervollständigen Sie Ihre Mitarbeiterdaten.
                Felder mit <span class="text-danger fw-bold">*</span> sind Pflichtfelder.
            </p>

            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                <strong>Bitte korrigieren Sie folgende Fehler:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li style="font-size:.88rem;">{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('employee.invitation.submit', $employee->invitation_token) }}" novalidate>
                @csrf

                {{-- ── Persönliche Daten ────────────────────────── --}}
                <p class="section-label">Persönliche Daten</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Vorname <span class="text-danger">*</span></label>
                        <input type="text" name="first_name"
                               class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name', $employee->first_name) }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nachname <span class="text-danger">*</span></label>
                        <input type="text" name="last_name"
                               class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $employee->last_name) }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsname <span class="badge-optional">optional</span></label>
                        <input type="text" name="birth_name"
                               class="form-control @error('birth_name') is-invalid @enderror"
                               value="{{ old('birth_name', $employee->birth_name) }}">
                        @error('birth_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsdatum <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth"
                               class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth', $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : '') }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Anschrift ─────────────────────────────────── --}}
                <p class="section-label">Anschrift</p>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Straße <span class="text-danger">*</span></label>
                        <input type="text" name="street"
                               class="form-control @error('street') is-invalid @enderror"
                               value="{{ old('street', $employee->street) }}" required>
                        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hausnummer <span class="text-danger">*</span></label>
                        <input type="text" name="house_number"
                               class="form-control @error('house_number') is-invalid @enderror"
                               value="{{ old('house_number', $employee->house_number) }}" required>
                        @error('house_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PLZ <span class="text-danger">*</span></label>
                        <input type="text" name="zip"
                               class="form-control @error('zip') is-invalid @enderror"
                               value="{{ old('zip', $employee->zip) }}" required>
                        @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Ort <span class="text-danger">*</span></label>
                        <input type="text" name="city"
                               class="form-control @error('city') is-invalid @enderror"
                               value="{{ old('city', $employee->city) }}" required>
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Land <span class="text-danger">*</span></label>
                        <input type="text" name="country"
                               class="form-control @error('country') is-invalid @enderror"
                               value="{{ old('country', $employee->country ?? 'Deutschland') }}" required>
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Kontakt ───────────────────────────────────── --}}
                <p class="section-label">Kontakt</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $employee->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobilnummer <span class="badge-optional">optional</span></label>
                        <input type="tel" name="phone_mobile"
                               class="form-control @error('phone_mobile') is-invalid @enderror"
                               value="{{ old('phone_mobile', $employee->phone_mobile) }}">
                        @error('phone_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Bankverbindung ────────────────────────────── --}}
                <p class="section-label">Bankverbindung <span class="badge-optional">optional</span></p>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="iban">IBAN</label>
                        <div class="input-group">
                            <input type="text" id="iban" name="iban"
                                   class="form-control @error('iban') is-invalid @enderror"
                                   value="{{ old('iban', $employee->iban) }}"
                                   placeholder="DE00 0000 0000 0000 0000 00"
                                   autocomplete="off"
                                   maxlength="34"
                                   spellcheck="false">
                            <span class="input-group-text bg-white" id="iban-spinner" style="display:none;">
                                <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                            </span>
                        </div>
                        <div id="iban-status" class="mt-1"></div>
                        @error('iban')<div class="text-danger mt-1" style="font-size:.85rem;">{{ $message }}</div>@enderror
                        <div class="form-text">Nach Eingabe werden BIC und Bankname automatisch ausgefüllt.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bic">BIC</label>
                        <input type="text" id="bic" name="bic"
                               class="form-control @error('bic') is-invalid @enderror"
                               value="{{ old('bic', $employee->bic) }}"
                               placeholder="wird automatisch gefüllt">
                        @error('bic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="bank_name">Geldinstitut</label>
                        <input type="text" id="bank_name" name="bank_name"
                               class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $employee->bank_name) }}"
                               placeholder="wird automatisch gefüllt">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Kontoinhaber <span class="badge-optional">optional</span></label>
                        <input type="text" name="account_holder"
                               class="form-control @error('account_holder') is-invalid @enderror"
                               value="{{ old('account_holder', $employee->account_holder) }}">
                        @error('account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Sozialversicherung ───────────────────────── --}}
                <p class="section-label">Sozialversicherung <span class="badge-optional">optional</span></p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sozialversicherungsnummer</label>
                        <input type="text" name="social_security_number"
                               class="form-control @error('social_security_number') is-invalid @enderror"
                               value="{{ old('social_security_number', $employee->social_security_number) }}"
                               placeholder="z. B. 65 170891 B 082" maxlength="12">
                        @error('social_security_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Submit ───────────────────────────────────── --}}
                <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                    <p class="mb-0 text-muted" style="font-size:.8rem;">
                        🔒 Ihre Daten werden verschlüsselt übertragen und gespeichert.
                    </p>
                    <button type="submit" class="btn btn-sp">
                        Daten absenden &nbsp;→
                    </button>
                </div>

            </form>
            @endif

        </div>{{-- /card-body --}}
    </div>{{-- /sp-card --}}

    <p class="text-center footer-note mt-3">
        &copy; {{ date('Y') }} StationPilot &middot; Alle Rechte vorbehalten
    </p>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const ibanInput  = document.getElementById('iban');
    const bicInput   = document.getElementById('bic');
    const bankInput  = document.getElementById('bank_name');
    const statusEl   = document.getElementById('iban-status');
    const spinner    = document.getElementById('iban-spinner');
    if (!ibanInput) return;

    // ── IBAN formatieren (Leerzeichen alle 4 Zeichen) ──────────────
    ibanInput.addEventListener('input', function () {
        let raw = this.value.replace(/\s+/g, '').toUpperCase();
        // Nur erlaubte Zeichen: A-Z, 0-9
        raw = raw.replace(/[^A-Z0-9]/g, '');
        // Gruppen à 4 Zeichen
        this.value = raw.match(/.{1,4}/g)?.join(' ') ?? raw;
        // Status zurücksetzen wenn Feld geleert
        if (!raw) {
            setStatus('', '');
            unmarkAutofilled();
        }
    });

    // ── Lookup beim Verlassen des Feldes ───────────────────────────
    ibanInput.addEventListener('blur', function () {
        const raw = this.value.replace(/\s+/g, '');
        if (raw.length < 15) return; // zu kurz für eine echte IBAN
        lookupIban(raw);
    });

    function lookupIban(iban) {
        setStatus('<span class="iban-loading">⟳ Bank wird gesucht…</span>', 'loading');
        spinner.style.display = '';

        fetch(`https://openiban.com/validate/${encodeURIComponent(iban)}?getBIC=true&validateBankCode=true`)
            .then(r => r.json())
            .then(data => {
                spinner.style.display = 'none';
                if (data.valid && data.bankData) {
                    const bic  = data.bankData.bic  || '';
                    const bank = data.bankData.name || '';
                    if (bic)  { bicInput.value  = bic;  bicInput.classList.add('autofilled'); }
                    if (bank) { bankInput.value = bank; bankInput.classList.add('autofilled'); }
                    setStatus(`✔ ${bank}${bic ? ' · ' + bic : ''}`, 'ok');
                } else {
                    setStatus('✘ IBAN nicht gefunden oder ungültig', 'err');
                    unmarkAutofilled();
                }
            })
            .catch(() => {
                spinner.style.display = 'none';
                setStatus('⚠ Banksuche nicht verfügbar – bitte manuell ausfüllen', 'err');
                unmarkAutofilled();
            });
    }

    function setStatus(html, type) {
        statusEl.innerHTML = html ? `<span class="iban-${type}">${html}</span>` : '';
    }

    function unmarkAutofilled() {
        bicInput.classList.remove('autofilled');
        bankInput.classList.remove('autofilled');
    }
})();
</script>
</body>
</html>
