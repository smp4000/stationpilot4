<x-filament-panels::page>

    @php $employee = $this->getEmployee(); @endphp

    @if (! $employee)
        <x-filament::section>
            <p class="text-gray-500 text-sm">
                Ihr Mitarbeiter-Profil wurde noch nicht vollständig eingerichtet.
                Bitte wenden Sie sich an Ihren Vorgesetzten.
            </p>
        </x-filament::section>
    @else

        {{-- Stationen-Übersicht --}}
        @php
            $stations = $employee->stations()->get();
            $primaryStation = $employee->station;
        @endphp

        @if ($primaryStation || $stations->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Meine Station(en)</x-slot>
                <div class="flex flex-wrap gap-2">
                    @if ($primaryStation)
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
                            {{ $primaryStation->name }}
                            <span class="ml-1 text-xs text-primary-500">(Primär)</span>
                        </span>
                    @endif
                    @foreach ($stations->where('id', '!=', optional($primaryStation)->id) as $station)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
                            {{ $station->name }}
                        </span>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Profildaten --}}
        <form wire:submit="saveProfile">
            {{ $this->profileForm }}

            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" color="primary">
                    Profil speichern
                </x-filament::button>
            </div>
        </form>

        {{-- Passwort ändern --}}
        <form wire:submit="changePassword" class="mt-6">
            {{ $this->passwordForm }}

            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" color="warning">
                    Passwort ändern
                </x-filament::button>
            </div>
        </form>

    @endif

    <x-filament-actions::modals />

</x-filament-panels::page>
