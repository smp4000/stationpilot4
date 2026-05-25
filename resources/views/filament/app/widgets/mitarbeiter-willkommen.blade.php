@php
    $employee = $this->getEmployee();
    $user     = auth()->user();
    $hour     = now()->hour;
    $greeting = match(true) {
        $hour < 12 => 'Guten Morgen',
        $hour < 18 => 'Guten Tag',
        default    => 'Guten Abend',
    };
@endphp

<div class="rounded-2xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-primary-700 to-primary-500 px-6 py-8">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-14 h-14 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold text-white">
                {{ strtoupper(substr($user->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
            </div>
            <div>
                <p class="text-primary-200 text-sm">{{ $greeting }},</p>
                <h2 class="text-white text-xl font-bold">{{ $user->first_name }} {{ $user->last_name }}</h2>
                @if ($employee?->station)
                    <p class="text-primary-200 text-sm mt-0.5 flex items-center gap-1">
                        <x-heroicon-s-map-pin class="w-3.5 h-3.5" />
                        {{ $employee->station->name }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Info-Kacheln --}}
    <div class="bg-white dark:bg-gray-900 px-6 py-5 grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Position</p>
            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                {{ $employee?->position ?: '—' }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Beschäftigungsart</p>
            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                {{ match($employee?->employment_type ?? '') {
                    'vollzeit'   => 'Vollzeit',
                    'teilzeit'   => 'Teilzeit',
                    'minijob'    => 'Minijob',
                    'aushilfe'   => 'Aushilfe',
                    'ausbildung' => 'Ausbildung',
                    default      => '—',
                } }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Eintrittsdatum</p>
            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                {{ $employee?->employment_start?->format('d.m.Y') ?? '—' }}
            </p>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Personalnummer</p>
            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200 font-mono">
                {{ $employee?->personnel_number ?: '—' }}
            </p>
        </div>

    </div>

    {{-- Passwort-Hinweis bei must_change_password --}}
    @if ($user->must_change_password ?? false)
        <div class="bg-amber-50 dark:bg-amber-900/20 border-t border-amber-200 dark:border-amber-800 px-6 py-3 flex items-center gap-3">
            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0" />
            <p class="text-sm text-amber-800 dark:text-amber-300">
                Bitte ändern Sie Ihr Passwort unter
                <a href="{{ \App\Filament\App\Pages\MeinProfil::getUrl() }}" class="font-semibold underline hover:no-underline">
                    Mein Profil
                </a>.
            </p>
        </div>
    @endif

</div>
