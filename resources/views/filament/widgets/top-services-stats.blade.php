<x-filament-widgets::widget>
    <x-filament::section heading="Servicios más vendidos">
        <div class="space-y-3">
            @foreach ($this->getServices() as $service)
                <div class="flex items-center justify-between rounded-xl border border-gray-700/50 bg-gray-900/40 p-4">
                    <div>
                        <p class="font-semibold">
                            {{ $service->name }}
                        </p>

                        <p class="text-sm text-gray-400">
                            Reservas realizadas
                        </p>
                    </div>

                    <div class="text-2xl font-bold">
                        {{ $service->completed_bookings_count }}
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>