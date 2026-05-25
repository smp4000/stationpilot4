<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Bereich – StationPilot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --sp-blue:      #1e3a8a;
            --sp-blue-mid:  #2563eb;
            --sp-blue-light:#dbeafe;
        }
        body { background: linear-gradient(160deg,#e8f0fe 0%,#f1f5f9 60%); min-height:100vh; }

        .sp-nav {
            background: linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            padding:16px 24px; display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 2px 12px rgba(30,58,138,.2);
        }
        .sp-nav-logo { display:inline-flex; align-items:center; gap:8px;
            background:rgba(255,255,255,.15); border-radius:8px; padding:5px 12px; }
        .sp-nav-logo span { color:#fff; font-weight:700; font-size:.9rem; letter-spacing:.5px; }
        .sp-nav-user { color:#bfdbfe; font-size:.9rem; }

        .sp-card { border:none; border-radius:16px; box-shadow:0 8px 32px rgba(30,58,138,.12); overflow:hidden; }
        .sp-card-header {
            background: linear-gradient(135deg,var(--sp-blue) 0%,var(--sp-blue-mid) 100%);
            padding:28px 32px; text-align:center;
        }
        .sp-card-header h2 { color:#fff; font-size:1.4rem; font-weight:700; margin:0 0 4px; }
        .sp-card-header p  { color:#bfdbfe; margin:0; font-size:.9rem; }
        .sp-card-body { background:#fff; padding:28px 32px; }

        .info-tile { background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px 20px; }
        .info-tile .label { font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
        .info-tile .value { font-size:1rem; font-weight:700; color:#0f172a; }

        .btn-sp-outline {
            border:2px solid var(--sp-blue-mid); color:var(--sp-blue-mid); background:#fff;
            border-radius:8px; padding:.55rem 1.2rem; font-weight:600; font-size:.9rem;
            text-decoration:none; display:inline-block; transition:all .2s;
        }
        .btn-sp-outline:hover { background:var(--sp-blue-mid); color:#fff; }

        .btn-sp-danger {
            border:2px solid #ef4444; color:#ef4444; background:#fff;
            border-radius:8px; padding:.55rem 1.2rem; font-weight:600; font-size:.9rem;
            cursor:pointer; transition:all .2s;
        }
        .btn-sp-danger:hover { background:#ef4444; color:#fff; }
    </style>
</head>
<body>

{{-- Navigation --}}
<nav class="sp-nav">
    <div class="sp-nav-logo">
        <span>&#9981; StationPilot</span>
    </div>
    <div class="sp-nav-user">
        {{ $employee->first_name }} {{ $employee->last_name }}
    </div>
</nav>

<div class="container py-5" style="max-width:640px;">

    @if(session('success'))
        <div class="alert alert-success rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="sp-card mb-4">
        <div class="sp-card-header">
            <div style="font-size:2.5rem;margin-bottom:8px;">&#128100;</div>
            <h2>Willkommen, {{ $employee->first_name }}!</h2>
            <p>Ihr persönlicher Mitarbeiter-Bereich</p>
        </div>
        <div class="sp-card-body">

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6">
                    <div class="info-tile">
                        <div class="label">Name</div>
                        <div class="value">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="info-tile">
                        <div class="label">Station</div>
                        <div class="value">{{ $employee->station->name ?? '—' }}</div>
                    </div>
                </div>
                @if($employee->email)
                <div class="col-12">
                    <div class="info-tile">
                        <div class="label">E-Mail</div>
                        <div class="value">{{ $employee->email }}</div>
                    </div>
                </div>
                @endif
            </div>

            <div class="alert" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#166534;font-size:.9rem;">
                Hier können Sie Ihre Daten einsehen. Weitere Funktionen folgen in Kürze.
            </div>

            <div class="d-flex gap-3 flex-wrap mt-4">
                <a href="{{ route('employee.portal.change-password') }}" class="btn-sp-outline">
                    &#128274; Passwort ändern
                </a>
                <form method="POST" action="{{ route('employee.portal.logout') }}">
                    @csrf
                    <button type="submit" class="btn-sp-danger">
                        &#128682; Abmelden
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
