<x-filament-panels::page>

    @php $employee = $this->getEmployee(); @endphp

    {{-- Stations-Übersicht --}}
    @if ($employee)
        @php
            $stations       = $employee->stations()->get();
            $primaryStation = $employee->station;
        @endphp
        @if ($primaryStation || $stations->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Meine Station(en)</x-slot>
                <div class="flex flex-wrap gap-2">
                    @if ($primaryStation)
                        <x-filament::badge color="primary" icon="heroicon-s-map-pin">
                            {{ $primaryStation->name }} (Primär)
                        </x-filament::badge>
                    @endif
                    @foreach ($stations->where('id', '!=', optional($primaryStation)->id) as $station)
                        <x-filament::badge color="gray" icon="heroicon-s-map-pin">
                            {{ $station->name }}
                        </x-filament::badge>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @endif

    {{-- Formulare --}}
    {{ $this->form }}

    <x-filament-actions::modals />

</x-filament-panels::page>
