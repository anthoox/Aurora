<x-filament-widgets::widget>
    <x-filament::section heading="Conversión por servicio">

        <div class="space-y-4">

            @foreach ($this->getServices() as $service)

                <div class="flex items-center justify-between">

                    <div>
                        <div class="font-medium">
                            {{ $service->name }}
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ $service->vendidos_count }}
                            vendidos de
                            {{ $service->interactions_count }}
                            leads
                        </div>
                    </div>

                    <div class="text-lg font-bold">
                        {{ $service->conversion_rate }}%
                    </div>

                </div>

            @endforeach

        </div>

    </x-filament::section>
</x-filament-widgets::widget>