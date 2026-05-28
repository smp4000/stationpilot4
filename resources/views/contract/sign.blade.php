<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Arbeitsvertrag unterschreiben</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; }
    .top-bar { background: #1e3a5f; color: #fff; padding: 14px 24px; display: flex; align-items: center; gap: 12px; }
    .top-bar h1 { font-size: 16px; }
    .top-bar .sub { font-size: 12px; opacity: .75; }
    .container { max-width: 860px; margin: 28px auto; padding: 0 16px 60px; }
    .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
    .card-header { padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-header h2 { font-size: 15px; font-weight: 700; }
    .card-header p { font-size: 12px; color: #64748b; margin-top: 4px; }
    .card-body { padding: 24px; }
    iframe { width: 100%; height: 650px; border: 1px solid #e2e8f0; border-radius: 6px; }
    .sig-section { margin-top: 24px; }
    .sig-section h3 { font-size: 13px; font-weight: 700; margin-bottom: 10px; }
    canvas#sigPad { border: 2px solid #cbd5e1; border-radius: 8px; width: 100%; height: 160px; cursor: crosshair; background: #f8fafc; touch-action: none; }
    .sig-controls { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
    .btn { padding: 9px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
    .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
    .btn-primary { background: #1e3a5f; color: #fff; }
    .btn-primary:hover { background: #162d4a; }
    .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }
    .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px; padding: 20px 24px; text-align: center; display: none; }
    .success-banner h2 { color: #065f46; font-size: 16px; }
    .success-banner p { color: #047857; margin-top: 6px; font-size: 13px; }
    .already-signed { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 16px 20px; text-align: center; }
    .hint { font-size: 11px; color: #94a3b8; margin-top: 8px; }
    @media (max-width: 600px) { iframe { height: 400px; } }
  </style>
</head>
<body>

<div class="top-bar">
  <div>
    <div class="h1">StationPilot · Arbeitsvertrag</div>
    <div class="sub">Digitale Unterschrift</div>
  </div>
</div>

<div class="container">

  @if(session('signed'))
  <div class="success-banner" style="display:block; margin-bottom:20px;">
    <h2>✅ Vielen Dank — Vertrag erfolgreich unterschrieben!</h2>
    <p>Ihr Exemplar wird Ihnen per E-Mail zugesandt. Sie können dieses Fenster schließen.</p>
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h2>Arbeitsvertrag — {{ $contract->employee->first_name }} {{ $contract->employee->last_name }}</h2>
      <p>
        Vertragsart: {{ $contract->contractTypeLabel() }} &nbsp;·&nbsp;
        Erstellt am {{ $contract->created_at->format('d.m.Y') }}
        @if($contract->employee_signed_at)
          &nbsp;·&nbsp; <span style="color:#059669;">✓ Unterschrieben am {{ $contract->employee_signed_at->format('d.m.Y H:i') }}</span>
        @endif
      </p>
    </div>
    <div class="card-body">

      {{-- PDF Vorschau --}}
      <iframe src="{{ route('contract.sign.pdf', $contract->employee_sign_token) }}"></iframe>

      {{-- Unterschrift --}}
      @if($contract->employee_signed_at)
        <div class="already-signed" style="margin-top:20px;">
          <p>✅ Sie haben diesen Vertrag bereits am <strong>{{ $contract->employee_signed_at->format('d.m.Y \u\m H:i \U\h\r') }}</strong> unterschrieben.</p>
        </div>
      @else
        <div class="sig-section">
          <h3>Hier unterschreiben (mit Maus oder Finger auf Touchscreen)</h3>
          <canvas id="sigPad"></canvas>
          <div class="sig-controls">
            <button class="btn btn-outline" onclick="clearSig()">Löschen</button>
            <form method="POST" action="{{ route('contract.sign.submit', $contract->employee_sign_token) }}" id="sigForm" style="display:flex; gap:10px; align-items:center;">
              @csrf
              <input type="hidden" name="signature" id="sigData">
              <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                Verbindlich unterschreiben &amp; absenden
              </button>
            </form>
          </div>
          <p class="hint">Mit dem Absenden bestätigen Sie, dass Sie den Vertrag gelesen haben und mit dem Inhalt einverstanden sind. Ihre Unterschrift ist rechtsverbindlich.</p>
        </div>
      @endif

    </div>
  </div>
</div>

<script>
const canvas = document.getElementById('sigPad');
const ctx    = canvas.getContext('2d');
const btn    = document.getElementById('submitBtn');
const sigData= document.getElementById('sigData');
let drawing  = false;
let hasSig   = false;

function resizeCanvas() {
  const ratio = window.devicePixelRatio || 1;
  const rect  = canvas.getBoundingClientRect();
  canvas.width  = rect.width  * ratio;
  canvas.height = rect.height * ratio;
  ctx.scale(ratio, ratio);
  ctx.strokeStyle = '#1e293b';
  ctx.lineWidth   = 2;
  ctx.lineCap     = 'round';
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

function getPos(e) {
  const rect = canvas.getBoundingClientRect();
  if (e.touches) {
    return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
  }
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

canvas.addEventListener('mousedown',  e => { drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
canvas.addEventListener('mousemove',  e => { if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasSig = true; updateBtn(); });
canvas.addEventListener('mouseup',    () => { drawing = false; });
canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }, { passive: false });
canvas.addEventListener('touchmove',  e => { e.preventDefault(); if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasSig = true; updateBtn(); }, { passive: false });
canvas.addEventListener('touchend',   () => { drawing = false; });

function clearSig() { ctx.clearRect(0, 0, canvas.width, canvas.height); hasSig = false; updateBtn(); }
function updateBtn() { btn.disabled = !hasSig; }

document.getElementById('sigForm')?.addEventListener('submit', function() {
  sigData.value = canvas.toDataURL('image/png');
});
</script>
</body>
</html>
