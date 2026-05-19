<x-filament-widgets::widget>
    <x-filament::section heading="Servicios más vendidos">
        <div class="space-y-4">
            @foreach ($this->getServices() as $service)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium">
                            {{ $service->name }}
                        </div>

                        <div class="text-sm text-gray-500">
                            Reservas realizadas
                        </div>
                    </div>

                    <div class="text-lg font-bold">
                        {{ $service->completed_bookings_count }}
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>