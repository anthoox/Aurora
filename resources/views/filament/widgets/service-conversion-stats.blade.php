<x-filament-widgets::widget>
    <x-filament::section heading="Conversión por servicio">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($this->getServices() as $service)
                <div class="rounded-xl border border-gray-700/50 bg-gray-900/40 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold">
                                {{ $service->name }}
                            </p>

                            <p class="text-sm text-gray-400">
                                {{ $service->vendidos_count }} vendidos de {{ $service->interactions_count }} leads
                            </p>
                        </div>

                        <div class="text-xl font-bold">
                            {{ $service->conversion_rate }}%
                        </div>
                    </div>

                    <div class="mt-4 h-2 rounded-full bg-gray-800">
                        <div class="h-2 rounded-full bg-primary-500" style="width: {{ $service->conversion_rate }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>