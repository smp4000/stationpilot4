<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1e293b; margin: 0; padding: 0; }
  .page { padding: 28mm 20mm 20mm; }
  .header { border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 24px; }
  .header h1 { font-size: 14pt; color: #1e3a5f; margin: 0 0 4px; }
  .header .meta { font-size: 9pt; color: #64748b; }
  .content h2 { font-size: 12pt; color: #1e293b; margin: 18px 0 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
  .content p { margin: 0 0 10px; line-height: 1.6; }
  .content ol, .content ul { margin: 0 0 10px; padding-left: 20px; }
  .content li { margin-bottom: 4px; line-height: 1.6; }
  .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 9pt; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <h1>{{ $template->name }}</h1>
    <div class="meta">Erstellt am {{ now()->format('d.m.Y') }}</div>
  </div>
  <div class="content">
    {!! $bodyHtml !!}
  </div>
  <div class="footer">
    Dieses Dokument wurde mit StationPilot erstellt &middot; {{ now()->format('d.m.Y H:i') }} Uhr
  </div>
</div>
</body>
</html>
