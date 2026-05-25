<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort ändern – StationPilot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --sp-blue:      #1e3a8a;
            --sp-blue-mid:  #2563eb;
            --sp-blue-light:#dbeafe;
        }
        body { background: linear-gradient(160deg,#e8f0fe 0%,#f1f5f9 60%); min-height:100vh; display:flex; align-items:center; justify-content:center; }

        .sp-card { border:none; border-radius:16px; box-shadow:0 8px 32px rgba(30,58,138,.12); overflow:hidden; max-width:480px; width:100%; }
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
        .sp-header h1 { color:#fff; font-size:1.4rem; font-weight:700; margin:0 0 4px; }
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

        .btn-back { font-size:.85rem; color:#64748b; text-decoration:none; }
        .btn-back:hover { color:var(--sp-blue-mid); }
    </style>
</head>
<body>

<div class="sp-card">
    {{-- Header --}}
    <div class="sp-header">
        <div class="sp-logo">
            <span>&#9981; StationPilot</span>
        </div>
        <h1>Passwort ändern</h1>
        <p>{{ $employee->first_name }} {{ $employee->last_name }}</p>
    </div>

    {{-- Body --}}
    <div class="sp-body">

        @if($employee->must_change_password)
            <div class="alert" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;color:#9a3412;font-size:.9rem;margin-bottom:20px;">
                <strong>Hinweis:</strong> Bitte ändern Sie Ihr temporäres Passwort, bevor Sie fortfahren.
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="border-radius:10px;font-size:.9rem;margin-bottom:20px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('employee.portal.change-password.post') }}">
            @csrf

            <div class="mb-3">
                <label for="password" class="form-label">Neues Passwort</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required minlength="8" placeholder="Mindestens 8 Zeichen">
                <div class="form-text" style="font-size:.8rem;color:#94a3b8;">Mindestens 8 Zeichen.</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Passwort bestätigen</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" required placeholder="Passwort wiederholen">
            </div>

            <button type="submit" class="btn btn-sp mb-3">Passwort speichern</button>

            @if(!$employee->must_change_password)
                <div class="text-center">
                    <a href="{{ route('employee.portal.dashboard') }}" class="btn-back">
                        &larr; Zurück zum Dashboard
                    </a>
                </div>
            @endif
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
