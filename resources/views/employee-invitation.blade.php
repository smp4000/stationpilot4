<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitarbeiterdaten – StationPilot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f0f4f8;
        }
        .card {
            border: none;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .page-header {
            background-color: #1e40af;
            color: #fff;
            padding: 28px 32px 24px;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .page-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }
        .page-header p {
            margin: 4px 0 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e40af;
            border-bottom: 2px solid #dbeafe;
            padding-bottom: 6px;
            margin-bottom: 18px;
            margin-top: 8px;
        }
        .btn-submit {
            background-color: #1e40af;
            border-color: #1e40af;
            font-size: 1rem;
            padding: 10px 32px;
        }
        .btn-submit:hover {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
        }
        .alert-icon {
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            <div class="card rounded">
                <div class="page-header">
                    <h1>StationPilot – Mitarbeiterdaten</h1>
                    <p>{{ $employee->station->name ?? 'Ihre Station' }}</p>
                </div>

                <div class="card-body p-4">

                    @if ($used)
                        {{-- Bereits übermittelt --}}
                        <div class="text-center py-5">
                            <div class="alert-icon mb-3">✅</div>
                            <h4 class="text-success">Daten bereits übermittelt</h4>
                            <p class="text-muted mt-2">
                                Ihre Mitarbeiterdaten wurden erfolgreich gespeichert.<br>
                                Vielen Dank, {{ $employee->first_name }}!
                            </p>
                        </div>

                    @elseif ($expired)
                        {{-- Link abgelaufen --}}
                        <div class="text-center py-5">
                            <div class="alert-icon mb-3">⏰</div>
                            <h4 class="text-danger">Einladungslink abgelaufen</h4>
                            <p class="text-muted mt-2">
                                Dieser Link ist leider nicht mehr gültig.<br>
                                Bitte wenden Sie sich an Ihren Arbeitgeber, um eine neue Einladung anzufordern.
                            </p>
                        </div>

                    @else
                        {{-- Formular --}}
                        <p class="mb-4">
                            Guten Tag, <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>!<br>
                            Bitte vervollständigen Sie Ihre Mitarbeiterdaten. Alle mit <span class="text-danger">*</span> markierten Felder sind Pflichtfelder.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Bitte korrigieren Sie folgende Fehler:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('employee.invitation.submit', $employee->invitation_token) }}" novalidate>
                            @csrf

                            {{-- Stammdaten --}}
                            <h5 class="section-title">Persönliche Daten</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Vorname <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                           value="{{ old('first_name', $employee->first_name) }}" required>
                                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nachname <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                           value="{{ old('last_name', $employee->last_name) }}" required>
                                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Geburtsname</label>
                                    <input type="text" name="birth_name" class="form-control @error('birth_name') is-invalid @enderror"
                                           value="{{ old('birth_name', $employee->birth_name) }}">
                                    @error('birth_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Geburtsdatum <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                           value="{{ old('date_of_birth', $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : '') }}" required>
                                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Anschrift --}}
                            <h5 class="section-title">Anschrift</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Straße <span class="text-danger">*</span></label>
                                    <input type="text" name="street" class="form-control @error('street') is-invalid @enderror"
                                           value="{{ old('street', $employee->street) }}" required>
                                    @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Hausnummer <span class="text-danger">*</span></label>
                                    <input type="text" name="house_number" class="form-control @error('house_number') is-invalid @enderror"
                                           value="{{ old('house_number', $employee->house_number) }}" required>
                                    @error('house_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">PLZ <span class="text-danger">*</span></label>
                                    <input type="text" name="zip" class="form-control @error('zip') is-invalid @enderror"
                                           value="{{ old('zip', $employee->zip) }}" required>
                                    @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Ort <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                           value="{{ old('city', $employee->city) }}" required>
                                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Land <span class="text-danger">*</span></label>
                                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                                           value="{{ old('country', $employee->country ?? 'Deutschland') }}" required>
                                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Kontakt --}}
                            <h5 class="section-title">Kontakt</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $employee->email) }}" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobilnummer</label>
                                    <input type="tel" name="phone_mobile" class="form-control @error('phone_mobile') is-invalid @enderror"
                                           value="{{ old('phone_mobile', $employee->phone_mobile) }}">
                                    @error('phone_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Bankverbindung --}}
                            <h5 class="section-title">Bankverbindung (optional)</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">IBAN</label>
                                    <input type="text" name="iban" class="form-control @error('iban') is-invalid @enderror"
                                           value="{{ old('iban', $employee->iban) }}" placeholder="DE00 0000 0000 0000 0000 00">
                                    @error('iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">BIC</label>
                                    <input type="text" name="bic" class="form-control @error('bic') is-invalid @enderror"
                                           value="{{ old('bic', $employee->bic) }}">
                                    @error('bic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Kontoinhaber</label>
                                    <input type="text" name="account_holder" class="form-control @error('account_holder') is-invalid @enderror"
                                           value="{{ old('account_holder', $employee->account_holder) }}">
                                    @error('account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Geldinstitut</label>
                                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                                           value="{{ old('bank_name', $employee->bank_name) }}">
                                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Sozialversicherung --}}
                            <h5 class="section-title">Sozialversicherung (optional)</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Sozialversicherungsnummer</label>
                                    <input type="text" name="social_security_number" class="form-control @error('social_security_number') is-invalid @enderror"
                                           value="{{ old('social_security_number', $employee->social_security_number) }}"
                                           placeholder="z. B. 65 170891 B 082" maxlength="12">
                                    @error('social_security_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                <button type="submit" class="btn btn-primary btn-submit">
                                    Daten absenden
                                </button>
                            </div>
                        </form>

                    @endif

                </div>{{-- /card-body --}}
            </div>{{-- /card --}}

            <p class="text-center text-muted mt-3" style="font-size: 0.8rem;">
                &copy; {{ date('Y') }} StationPilot – Alle Rechte vorbehalten
            </p>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
