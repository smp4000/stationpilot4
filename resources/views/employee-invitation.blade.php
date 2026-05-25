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
            --sp-green:     #16a34a;
        }
        body { background: linear-gradient(160deg,#e8f0fe 0%,#f1f5f9 60%); min-height:100vh; }

        /* ── Karte ───────────────────────────────────────────── */
        .sp-card { border:none; border-radius:16px; box-shadow:0 8px 32px rgba(30,58,138,.12); overflow:hidden; }
        .sp-header {
            background: linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            padding:32px 36px 28px; position:relative;
        }
        .sp-header::after {
            content:''; position:absolute; bottom:-1px; left:0; right:0;
            height:20px; background:#fff; border-radius:20px 20px 0 0;
        }
        .sp-logo { display:inline-flex; align-items:center; gap:8px;
            background:rgba(255,255,255,.15); border-radius:8px; padding:5px 12px; margin-bottom:16px; }
        .sp-logo span { color:#fff; font-weight:700; font-size:.9rem; letter-spacing:.5px; }
        .sp-header h1 { color:#fff; font-size:1.4rem; font-weight:700; margin:0 0 4px; }
        .sp-header p  { color:#bfdbfe; margin:0; font-size:.85rem; }

        /* ── Stepper ─────────────────────────────────────────── */
        .stepper {
            display:flex; align-items:flex-start; justify-content:center;
            padding:20px 16px 12px; gap:0; background:#fff;
        }
        .step-item { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
        .step-item:not(:last-child)::after {
            content:''; position:absolute; top:16px; left:calc(50% + 16px);
            right:calc(-50% + 16px); height:2px;
            background:var(--sp-blue-light); z-index:0;
        }
        .step-item.done:not(:last-child)::after  { background:var(--sp-blue-mid); }
        .step-circle {
            width:32px; height:32px; border-radius:50%; border:2px solid #e2e8f0;
            background:#fff; display:flex; align-items:center; justify-content:center;
            font-size:.75rem; font-weight:700; color:#94a3b8;
            position:relative; z-index:1; transition:all .25s;
        }
        .step-item.active .step-circle { border-color:var(--sp-blue-mid); background:var(--sp-blue-mid); color:#fff; }
        .step-item.done   .step-circle { border-color:var(--sp-green); background:var(--sp-green); color:#fff; }
        .step-label { font-size:.65rem; color:#94a3b8; margin-top:5px; text-align:center; font-weight:600; white-space:nowrap; }
        .step-item.active .step-label { color:var(--sp-blue-mid); }
        .step-item.done   .step-label { color:var(--sp-green); }

        /* ── Abschnitte ──────────────────────────────────────── */
        .section-label {
            font-size:.72rem; font-weight:700; letter-spacing:.8px;
            text-transform:uppercase; color:var(--sp-blue-mid);
            border-bottom:2px solid var(--sp-blue-light);
            padding-bottom:5px; margin:20px 0 14px;
        }
        .section-label:first-of-type { margin-top:0; }
        .form-label { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:3px; }
        .form-control, .form-select {
            border-radius:8px; border:1.5px solid #e5e7eb;
            padding:9px 13px; font-size:.88rem;
            transition:border-color .15s,box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color:var(--sp-blue-mid); box-shadow:0 0 0 3px rgba(37,99,235,.1);
        }
        .badge-opt {
            font-size:.6rem; font-weight:700; background:#f1f5f9; color:#64748b;
            border-radius:4px; padding:2px 5px; margin-left:5px;
            text-transform:uppercase; letter-spacing:.4px; vertical-align:middle;
        }

        /* ── IBAN ────────────────────────────────────────────── */
        #iban-status { min-height:20px; font-size:.8rem; }
        .iban-ok  { color:#16a34a; font-weight:600; }
        .iban-err { color:#dc2626; font-weight:600; }
        #bic,#bank_name { background:#f9fafb; transition:background .3s; }
        #bic.autofilled,#bank_name.autofilled { background:#ecfdf5; border-color:#86efac; }

        /* ── Navigation ──────────────────────────────────────── */
        .step-nav { display:flex; justify-content:space-between; align-items:center;
            padding-top:20px; border-top:1px solid #f1f5f9; margin-top:8px; }
        .btn-sp {
            background:linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            border:none; border-radius:9px; font-size:.9rem; font-weight:700;
            padding:10px 28px; color:#fff; box-shadow:0 3px 10px rgba(37,99,235,.3);
            transition:opacity .15s,transform .1s;
        }
        .btn-sp:hover { opacity:.9; transform:translateY(-1px); color:#fff; }
        .btn-back {
            background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px;
            font-size:.9rem; font-weight:600; padding:9px 24px; color:#64748b;
            transition:background .15s;
        }
        .btn-back:hover { background:#f1f5f9; color:#374151; }
        .step-counter { font-size:.8rem; color:#94a3b8; }

        /* ── Status-Seiten ───────────────────────────────────── */
        .status-icon { font-size:3.5rem; }
        .footer-note { font-size:.78rem; color:#94a3b8; }

        /* ── Schritt-Panels (hidden/visible) ─────────────────── */
        .step-panel { display:none; }
        .step-panel.active { display:block; }

        /* ── Progress-Bar ────────────────────────────────────── */
        .sp-progress { height:4px; background:var(--sp-blue-light); }
        .sp-progress-bar { height:4px; background:var(--sp-blue-mid); transition:width .4s ease; border-radius:0; }
    </style>
</head>
<body>

<div class="container py-4 py-md-5">
<div class="row justify-content-center">
<div class="col-xl-7 col-lg-8 col-md-10">

<div class="sp-card bg-white">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="sp-header">
        <div class="sp-logo"><span>⛽ StationPilot</span></div>
        <h1>Mitarbeiterdaten</h1>
        <p>{{ $employee->station->name ?? 'Ihre Tankstelle' }}</p>
    </div>

    @if ($used)
    {{-- ── Fertig ───────────────────────────────────────────────── --}}
    <div class="card-body p-4 text-center py-5">
        <div class="status-icon mb-3">🎉</div>
        <h4 class="fw-bold text-success mb-2">Vielen Dank, {{ $employee->first_name }}!</h4>
        <p class="text-muted">Ihre Mitarbeiterdaten wurden erfolgreich übermittelt.</p>
        <div class="mt-4 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <p class="mb-0 text-success" style="font-size:.9rem;">✔ Formular abgeschlossen · Kein weiterer Handlungsbedarf</p>
        </div>
    </div>

    @elseif ($expired)
    {{-- ── Abgelaufen ───────────────────────────────────────────── --}}
    <div class="card-body p-4 text-center py-5">
        <div class="status-icon mb-3">⏰</div>
        <h4 class="fw-bold text-danger mb-2">Einladungslink abgelaufen</h4>
        <p class="text-muted">Bitte wenden Sie sich an Ihren Arbeitgeber für eine neue Einladung.</p>
    </div>

    @else
    {{-- ── Stepper ──────────────────────────────────────────────── --}}
    <div class="stepper" id="stepper">
        <div class="step-item active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">Person</div>
        </div>
        <div class="step-item" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">Adresse</div>
        </div>
        <div class="step-item" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">Steuer</div>
        </div>
        <div class="step-item" data-step="4">
            <div class="step-circle">4</div>
            <div class="step-label">Bank</div>
        </div>
        <div class="step-item" data-step="5">
            <div class="step-circle">5</div>
            <div class="step-label">Notfall</div>
        </div>
    </div>
    <div class="sp-progress"><div class="sp-progress-bar" id="progressBar" style="width:20%"></div></div>

    {{-- Fehler-Box (sichtbar wenn Validation fehlschlug) --}}
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3 mb-0 rounded-3" role="alert">
        <strong>Bitte korrigieren Sie folgende Fehler:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach ($errors->all() as $error)
                <li style="font-size:.85rem;">{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-body p-4 pt-3">
        <p class="text-muted mb-3" style="font-size:.88rem;">
            Guten Tag, <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>!
            Bitte füllen Sie alle Schritte aus. Felder mit <span class="text-danger fw-bold">*</span> sind Pflichtfelder.
        </p>

        <form method="POST" action="{{ route('employee.invitation.submit', $employee->invitation_token) }}" novalidate id="invForm">
            @csrf

            {{-- ══════════════════════════════════════════════════
                 SCHRITT 1 – Persönliche Daten
            ══════════════════════════════════════════════════════ --}}
            <div class="step-panel active" id="panel-1">
                <p class="section-label">Persönliche Daten</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Vorname <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" required
                               class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name', $employee->first_name) }}">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nachname <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" required
                               class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $employee->last_name) }}">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsname <span class="badge-opt">opt.</span></label>
                        <input type="text" name="birth_name"
                               class="form-control @error('birth_name') is-invalid @enderror"
                               value="{{ old('birth_name', $employee->birth_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsdatum <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" required
                               class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth', $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : '') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsort <span class="badge-opt">opt.</span></label>
                        <input type="text" name="place_of_birth"
                               class="form-control @error('place_of_birth') is-invalid @enderror"
                               value="{{ old('place_of_birth', $employee->place_of_birth) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geburtsland <span class="badge-opt">opt.</span></label>
                        <input type="text" name="country_of_birth"
                               class="form-control @error('country_of_birth') is-invalid @enderror"
                               placeholder="z. B. Deutschland"
                               value="{{ old('country_of_birth', $employee->country_of_birth ?? 'Deutschland') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Staatsangehörigkeit <span class="badge-opt">opt.</span></label>
                        <input type="text" name="nationality"
                               class="form-control @error('nationality') is-invalid @enderror"
                               placeholder="z. B. deutsch"
                               value="{{ old('nationality', $employee->nationality ?? 'deutsch') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Geschlecht <span class="badge-opt">opt.</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">– bitte wählen –</option>
                            <option value="m" {{ old('gender', $employee->gender) === 'm' ? 'selected' : '' }}>Männlich</option>
                            <option value="w" {{ old('gender', $employee->gender) === 'w' ? 'selected' : '' }}>Weiblich</option>
                            <option value="d" {{ old('gender', $employee->gender) === 'd' ? 'selected' : '' }}>Divers</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Familienstand <span class="badge-opt">opt.</span></label>
                        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                            <option value="">– bitte wählen –</option>
                            @foreach(['ledig'=>'Ledig','verheiratet'=>'Verheiratet','geschieden'=>'Geschieden','verwitwet'=>'Verwitwet','eingetragen'=>'Eingetragene Lebenspartnerschaft'] as $val=>$label)
                                <option value="{{ $val }}" {{ old('marital_status', $employee->marital_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="step-nav">
                    <span class="step-counter">Schritt 1 von 5</span>
                    <button type="button" class="btn-sp btn-next" data-next="2">Weiter →</button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 SCHRITT 2 – Anschrift & Kontakt
            ══════════════════════════════════════════════════════ --}}
            <div class="step-panel" id="panel-2">
                <p class="section-label">Anschrift</p>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Straße <span class="text-danger">*</span></label>
                        <input type="text" name="street" required
                               class="form-control @error('street') is-invalid @enderror"
                               value="{{ old('street', $employee->street) }}">
                        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hausnummer <span class="text-danger">*</span></label>
                        <input type="text" name="house_number" required
                               class="form-control @error('house_number') is-invalid @enderror"
                               value="{{ old('house_number', $employee->house_number) }}">
                        @error('house_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PLZ <span class="text-danger">*</span></label>
                        <input type="text" name="zip" required
                               class="form-control @error('zip') is-invalid @enderror"
                               value="{{ old('zip', $employee->zip) }}">
                        @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Ort <span class="text-danger">*</span></label>
                        <input type="text" name="city" required
                               class="form-control @error('city') is-invalid @enderror"
                               value="{{ old('city', $employee->city) }}">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Land <span class="text-danger">*</span></label>
                        <input type="text" name="country" required
                               class="form-control @error('country') is-invalid @enderror"
                               value="{{ old('country', $employee->country ?? 'Deutschland') }}">
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <p class="section-label">Kontakt</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                        <input type="email" name="email" required
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $employee->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon (privat) <span class="badge-opt">opt.</span></label>
                        <input type="tel" name="phone_private"
                               class="form-control @error('phone_private') is-invalid @enderror"
                               value="{{ old('phone_private', $employee->phone_private) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobilnummer <span class="badge-opt">opt.</span></label>
                        <input type="tel" name="phone_mobile"
                               class="form-control @error('phone_mobile') is-invalid @enderror"
                               value="{{ old('phone_mobile', $employee->phone_mobile) }}">
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-back btn-prev" data-prev="1">← Zurück</button>
                    <span class="step-counter">Schritt 2 von 5</span>
                    <button type="button" class="btn-sp btn-next" data-next="3">Weiter →</button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 SCHRITT 3 – Steuer & Sozialversicherung
            ══════════════════════════════════════════════════════ --}}
            <div class="step-panel" id="panel-3">
                <p class="section-label">Steuer</p>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Steuer-Identifikationsnummer <span class="badge-opt">opt.</span></label>
                        <input type="text" name="tax_id" maxlength="11"
                               class="form-control @error('tax_id') is-invalid @enderror"
                               placeholder="11-stellige Nummer"
                               value="{{ old('tax_id', $employee->tax_id) }}">
                        <div class="form-text">🔒 Verschlüsselt gespeichert</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Steuerklasse <span class="badge-opt">opt.</span></label>
                        <select name="tax_class" class="form-select @error('tax_class') is-invalid @enderror">
                            <option value="">–</option>
                            @for($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}" {{ old('tax_class', $employee->tax_class) == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kinderfreibeträge <span class="badge-opt">opt.</span></label>
                        <input type="number" name="tax_child_allowance" min="0" max="9" step="0.5"
                               class="form-control @error('tax_child_allowance') is-invalid @enderror"
                               placeholder="0 – 9"
                               value="{{ old('tax_child_allowance', $employee->tax_child_allowance) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Konfession / Kirchensteuer <span class="badge-opt">opt.</span></label>
                        <select name="church_tax" class="form-select @error('church_tax') is-invalid @enderror">
                            <option value="">– keine –</option>
                            <option value="keine" {{ old('church_tax', $employee->church_tax) === 'keine' ? 'selected' : '' }}>Keine Kirchensteuer</option>
                            <option value="ev"    {{ old('church_tax', $employee->church_tax) === 'ev'    ? 'selected' : '' }}>Evangelisch</option>
                            <option value="rk"    {{ old('church_tax', $employee->church_tax) === 'rk'    ? 'selected' : '' }}>Römisch-Katholisch</option>
                            <option value="ak"    {{ old('church_tax', $employee->church_tax) === 'ak'    ? 'selected' : '' }}>Alt-Katholisch</option>
                            <option value="fr"    {{ old('church_tax', $employee->church_tax) === 'fr'    ? 'selected' : '' }}>Freireligiös</option>
                            <option value="jd"    {{ old('church_tax', $employee->church_tax) === 'jd'    ? 'selected' : '' }}>Jüdisch</option>
                        </select>
                    </div>
                </div>

                <p class="section-label">Sozialversicherung & Krankenkasse</p>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Sozialversicherungsnummer <span class="badge-opt">opt.</span></label>
                        <input type="text" name="social_security_number" maxlength="20"
                               class="form-control @error('social_security_number') is-invalid @enderror"
                               placeholder="z. B. 65 170891 B 082"
                               value="{{ old('social_security_number', $employee->social_security_number) }}">
                        <div class="form-text">🔒 Verschlüsselt gespeichert</div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Krankenkasse <span class="badge-opt">opt.</span></label>
                        <input type="text" name="health_insurance_name"
                               class="form-control @error('health_insurance_name') is-invalid @enderror"
                               placeholder="z. B. AOK, TK, Barmer …"
                               value="{{ old('health_insurance_name', $employee->health_insurance_name) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Versicherungsart <span class="badge-opt">opt.</span></label>
                        <select name="health_insurance_type" class="form-select @error('health_insurance_type') is-invalid @enderror">
                            <option value="">– bitte wählen –</option>
                            @foreach(['pflicht'=>'Gesetzlich pflichtversichert','freiwillig'=>'Freiwillig gesetzlich','privat'=>'Privat versichert','befreit'=>'Befreit'] as $v=>$l)
                                <option value="{{ $v }}" {{ old('health_insurance_type', $employee->health_insurance_type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-back btn-prev" data-prev="2">← Zurück</button>
                    <span class="step-counter">Schritt 3 von 5</span>
                    <button type="button" class="btn-sp btn-next" data-next="4">Weiter →</button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 SCHRITT 4 – Bankverbindung
            ══════════════════════════════════════════════════════ --}}
            <div class="step-panel" id="panel-4">
                <p class="section-label">Bankverbindung <span class="badge-opt">optional</span></p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="iban">IBAN</label>
                        <div class="input-group">
                            <input type="text" id="iban" name="iban"
                                   class="form-control @error('iban') is-invalid @enderror"
                                   value="{{ old('iban', $employee->iban) }}"
                                   placeholder="DE00 0000 0000 0000 0000 00"
                                   autocomplete="off" maxlength="34" spellcheck="false">
                            <span class="input-group-text bg-white" id="iban-spinner" style="display:none;">
                                <span class="spinner-border spinner-border-sm text-secondary"></span>
                            </span>
                        </div>
                        <div id="iban-status" class="mt-1"></div>
                        @error('iban')<div class="text-danger mt-1" style="font-size:.83rem;">{{ $message }}</div>@enderror
                        <div class="form-text">BIC und Geldinstitut werden nach Eingabe automatisch ausgefüllt.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bic">BIC</label>
                        <input type="text" id="bic" name="bic"
                               class="form-control @error('bic') is-invalid @enderror"
                               value="{{ old('bic', $employee->bic) }}"
                               placeholder="automatisch">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="bank_name">Geldinstitut</label>
                        <input type="text" id="bank_name" name="bank_name"
                               class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $employee->bank_name) }}"
                               placeholder="automatisch">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Kontoinhaber <span class="badge-opt">opt.</span></label>
                        <input type="text" name="account_holder"
                               class="form-control @error('account_holder') is-invalid @enderror"
                               value="{{ old('account_holder', $employee->account_holder) }}">
                    </div>
                </div>
                <div class="step-nav">
                    <button type="button" class="btn-back btn-prev" data-prev="3">← Zurück</button>
                    <span class="step-counter">Schritt 4 von 5</span>
                    <button type="button" class="btn-sp btn-next" data-next="5">Weiter →</button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 SCHRITT 5 – Notfallkontakt
            ══════════════════════════════════════════════════════ --}}
            <div class="step-panel" id="panel-5">
                <p class="section-label">Notfallkontakt <span class="badge-opt">optional</span></p>
                <p class="text-muted mb-3" style="font-size:.85rem;">
                    Wen sollen wir im Notfall benachrichtigen? Diese Angabe ist freiwillig.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="emergency_name"
                               class="form-control"
                               placeholder="Vor- und Nachname"
                               value="{{ old('emergency_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Beziehung</label>
                        <input type="text" name="emergency_relationship"
                               class="form-control"
                               placeholder="z. B. Ehepartner, Mutter, Bruder"
                               value="{{ old('emergency_relationship') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="emergency_phone"
                               class="form-control"
                               value="{{ old('emergency_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobil</label>
                        <input type="tel" name="emergency_phone_mobile"
                               class="form-control"
                               value="{{ old('emergency_phone_mobile') }}">
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <p class="mb-0" style="font-size:.82rem;color:#64748b;">
                        🔒 Alle Daten werden verschlüsselt übertragen und DSGVO-konform gespeichert.
                    </p>
                </div>

                <div class="step-nav">
                    <button type="button" class="btn-back btn-prev" data-prev="4">← Zurück</button>
                    <span class="step-counter">Schritt 5 von 5</span>
                    <button type="submit" class="btn-sp">
                        ✓ Daten absenden
                    </button>
                </div>
            </div>

        </form>
    </div>{{-- /card-body --}}
    @endif

</div>{{-- /sp-card --}}

<p class="text-center footer-note mt-3">
    &copy; {{ date('Y') }} StationPilot &middot; Alle Rechte vorbehalten
</p>

</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const TOTAL = 5;
    let currentStep = {{ $errors->any() ? max(1, (int) old('_step', 1)) : 1 }};

    // ── Schritt wechseln ─────────────────────────────────────────
    function goTo(step) {
        // Pflichtfelder im aktuellen Schritt prüfen (nur Vorwärts)
        if (step > currentStep) {
            const panel = document.getElementById('panel-' + currentStep);
            const required = panel.querySelectorAll('[required]');
            let valid = true;
            required.forEach(el => {
                if (!el.value.trim()) {
                    el.classList.add('is-invalid');
                    valid = false;
                } else {
                    el.classList.remove('is-invalid');
                }
            });
            if (!valid) {
                panel.querySelector('[required]:invalid, .is-invalid')?.focus();
                return;
            }
        }

        // Panel-Sichtbarkeit
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + step).classList.add('active');

        // Stepper-Zustand
        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.dataset.step);
            item.classList.remove('active', 'done');
            if (s === step)  item.classList.add('active');
            if (s < step)    item.classList.add('done');
            // done: Häkchen
            const circle = item.querySelector('.step-circle');
            circle.textContent = s < step ? '✓' : s;
        });

        // Fortschrittsbalken
        document.getElementById('progressBar').style.width = (step / TOTAL * 100) + '%';

        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Buttons
    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => goTo(parseInt(btn.dataset.next)));
    });
    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => goTo(parseInt(btn.dataset.prev)));
    });

    // Bei Validation-Fehler nach POST: korrekten Schritt anzeigen
    @if ($errors->any())
        // Ersten Fehler-Tab anzeigen
        const errorFields = {
            'first_name':1,'last_name':1,'birth_name':1,'date_of_birth':1,
            'place_of_birth':1,'country_of_birth':1,'nationality':1,'gender':1,'marital_status':1,
            'street':2,'house_number':2,'zip':2,'city':2,'country':2,'email':2,'phone_private':2,'phone_mobile':2,
            'tax_id':3,'tax_class':3,'tax_child_allowance':3,'church_tax':3,
            'health_insurance_name':3,'health_insurance_type':3,'social_security_number':3,
            'iban':4,'bic':4,'bank_name':4,'account_holder':4,
            'emergency_name':5,'emergency_relationship':5,'emergency_phone':5,'emergency_phone_mobile':5,
        };
        const firstError = @json(array_key_first($errors->messages()));
        const errorStep = errorFields[firstError] || 1;
        // Direkt setzen ohne Validierung
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + errorStep).classList.add('active');
        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.dataset.step);
            item.classList.remove('active','done');
            if (s === errorStep) item.classList.add('active');
            if (s < errorStep)   { item.classList.add('done'); item.querySelector('.step-circle').textContent = '✓'; }
        });
        document.getElementById('progressBar').style.width = (errorStep / TOTAL * 100) + '%';
        currentStep = errorStep;
    @endif

    // ── IBAN-Autofill ─────────────────────────────────────────────
    const ibanInput = document.getElementById('iban');
    const bicInput  = document.getElementById('bic');
    const bankInput = document.getElementById('bank_name');
    const statusEl  = document.getElementById('iban-status');
    const spinner   = document.getElementById('iban-spinner');
    if (!ibanInput) return;

    ibanInput.addEventListener('input', function () {
        let raw = this.value.replace(/\s+/g,'').toUpperCase().replace(/[^A-Z0-9]/g,'');
        this.value = raw.match(/.{1,4}/g)?.join(' ') ?? raw;
        if (!raw) { statusEl.innerHTML = ''; unmark(); }
    });

    ibanInput.addEventListener('blur', function () {
        const raw = this.value.replace(/\s+/g,'');
        if (raw.length < 15) return;
        spinner.style.display = '';
        statusEl.innerHTML = '<span style="color:#6b7280;font-size:.8rem;">⟳ Bank wird gesucht…</span>';
        fetch('https://openiban.com/validate/' + encodeURIComponent(raw) + '?getBIC=true&validateBankCode=true')
            .then(r => r.json())
            .then(data => {
                spinner.style.display = 'none';
                if (data.valid && data.bankData) {
                    if (data.bankData.bic)  { bicInput.value  = data.bankData.bic;  bicInput.classList.add('autofilled'); }
                    if (data.bankData.name) { bankInput.value = data.bankData.name; bankInput.classList.add('autofilled'); }
                    statusEl.innerHTML = '<span class="iban-ok">✔ ' + (data.bankData.name || '') + (data.bankData.bic ? ' · ' + data.bankData.bic : '') + '</span>';
                } else {
                    statusEl.innerHTML = '<span class="iban-err">✘ IBAN ungültig oder nicht gefunden</span>';
                    unmark();
                }
            })
            .catch(() => {
                spinner.style.display = 'none';
                statusEl.innerHTML = '<span class="iban-err">⚠ Banksuche nicht verfügbar – bitte manuell ausfüllen</span>';
            });
    });

    function unmark() { bicInput.classList.remove('autofilled'); bankInput.classList.remove('autofilled'); }
})();
</script>
</body>
</html>
