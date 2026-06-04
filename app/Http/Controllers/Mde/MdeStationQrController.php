<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\Station;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;

/**
 * QR-Code für Stations-Einrichtung am MDE-Gerät.
 *
 * GET /mde/station/{ulid}/qr
 *   → SVG QR-Code mit Station-ULID als Inhalt
 *   → Wird in Filament angezeigt, Mitarbeiter scannt mit GoPilot
 */
class MdeStationQrController extends Controller
{
    public function show(string $ulid): Response
    {
        $station = Station::where('ulid', $ulid)->firstOrFail();

        // QR-Inhalt: nur die ULID — App sendet diese an /api/mde/device/register
        $qrContent = $station->ulid;

        $renderer = new ImageRenderer(
            new RendererStyle(300, 2),
            new SvgImageBackEnd(),
        );

        $writer = new Writer($renderer);
        $svg    = $writer->writeString($qrContent);

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
