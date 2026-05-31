<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentSigningController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $document = GeneratedDocument::where('sign_token', $token)
            ->with('template')
            ->firstOrFail();

        return view('document.sign', compact('document'));
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $document = GeneratedDocument::where('sign_token', $token)->firstOrFail();

        if ($document->signed_at) {
            return redirect()->route('document.sign', $token)->with('signed', true);
        }

        $request->validate(['signature' => 'required|string']);

        $document->update([
            'signature' => $request->input('signature'),
            'signed_at' => now(),
        ]);

        return redirect()->route('document.sign', $token)->with('signed', true);
    }

    public function pdf(string $token): \Illuminate\Http\Response|StreamedResponse
    {
        $document = GeneratedDocument::where('sign_token', $token)->firstOrFail();

        if ($document->pdf_path && Storage::disk('local')->exists($document->pdf_path)) {
            return Storage::disk('local')->response($document->pdf_path, null, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]);
        }

        abort(404);
    }

    public function download(int $id): StreamedResponse
    {
        $document = GeneratedDocument::where('id', $id)
            ->where('tenant_id', session('tenant_id'))
            ->with('template')
            ->firstOrFail();

        abort_unless($document->pdf_path && Storage::disk('local')->exists($document->pdf_path), 404);

        $filename = ($document->template?->name ?? 'Dokument') . '.pdf';
        return Storage::disk('local')->download($document->pdf_path, $filename);
    }
}
