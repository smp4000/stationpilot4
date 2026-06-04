<div class="flex flex-col items-center gap-4 py-4">

    {{-- QR-Code Bild --}}
    <div class="rounded-2xl border-4 border-primary-500 p-3 bg-white shadow-lg">
        <img
            src="{{ $qrUrl }}"
            alt="GoPilot QR-Code für {{ $stationName }}"
            class="w-64 h-64"
        />
    </div>

    {{-- Stationsname --}}
    <div class="text-center">
        <p class="text-sm font-semibold text-gray-700">{{ $stationName }}</p>
        <p class="text-xs text-gray-400 font-mono mt-1">{{ $stationUlid }}</p>
    </div>

    {{-- Anleitung --}}
    <div class="w-full rounded-xl bg-primary-50 border border-primary-200 p-4 text-sm text-primary-800 space-y-2">
        <p class="font-semibold">📱 So verbindest du ein GoPilot-Gerät:</p>
        <ol class="list-decimal list-inside space-y-1 text-primary-700">
            <li>GoPilot App öffnen</li>
            <li>„Gerät einrichten" antippen</li>
            <li>Kamera-Button drücken und diesen QR-Code scannen</li>
            <li>Gerätename eingeben und bestätigen</li>
        </ol>
    </div>

    {{-- Drucken Button --}}
    <button
        onclick="window.print()"
        class="flex items-center gap-2 rounded-lg bg-gray-100 hover:bg-gray-200 px-4 py-2 text-sm text-gray-700 transition"
    >
        🖨️ QR-Code drucken
    </button>

</div>
