<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitarbeiter-Login – StationPilot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --sp-blue:      #1e3a8a;
            --sp-blue-mid:  #2563eb;
            --sp-blue-light:#dbeafe;
        }
        body { background: linear-gradient(160deg,#e8f0fe 0%,#f1f5f9 60%); min-height:100vh; display:flex; align-items:center; justify-content:center; }

        .sp-card { border:none; border-radius:16px; box-shadow:0 8px 32px rgba(30,58,138,.12); overflow:hidden; max-width:440px; width:100%; }
        .sp-header {
            background: linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            padding:32px 36px 28px; position:relative; text-align:center;
        }
        .sp-header::after {
            content:''; position:absolute; bottom:-1px; left:0; right:0;
            height:20px; background:#fff; border-radius:20px 20px 0 0;
        }
        .sp-logo { display:inline-flex; align-items:center; gap:8px;
            background:rgba(255,255,255,.15); border-radius:8px; padding:5px 12px; margin-bottom:16px; }
        .sp-logo span { color:#fff; font-weight:700; font-size:.9rem; letter-spacing:.5px; }
        .sp-header h1 { color:#fff; font-size:1.5rem; font-weight:700; margin:0 0 4px; }
        .sp-header p  { color:#bfdbfe; margin:0; font-size:.85rem; }

        .sp-body { background:#fff; padding:32px 36px 36px; }

        .form-label { font-size:.85rem; font-weight:600; color:#475569; }
        .form-control { border-radius:8px; border:1.5px solid #e2e8f0; padding:.6rem .9rem; font-size:.95rem; }
        .form-control:focus { border-color:var(--sp-blue-mid); box-shadow:0 0 0 3px rgba(37,99,235,.15); }

        .btn-sp {
            background: linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            color:#fff; border:none; border-radius:8px; padding:.75rem 1.5rem;
            font-weight:700; font-size:1rem; width:100%;
            box-shadow:0 4px 12px rgba(37,99,235,.3); transition:opacity .2s;
        }
        .btn-sp:hover { opacity:.9; color:#fff; }

        .alert-sp { border-radius:8px; font-size:.9rem; }
    </style>
</head>
<body>

<div class="sp-card">
    {{-- Header --}}
    <div class="sp-header">
        <div class="sp-logo">
            <span>&#9981; StationPilot</span>
        </div>
        <h1>Mitarbeiter-Login</h1>
        <p>Melden Sie sich mit Ihren Zugangsdaten an</p>
    </div>

    {{-- Body --}}
    <div class="sp-body">

        @if(session('success'))
            <div class="alert alert-success alert-sp mb-4">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-sp mb-4">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('employee.portal.login.post') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus placeholder="ihre@email.de">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>

            <button type="submit" class="btn btn-sp">Anmelden</button>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
