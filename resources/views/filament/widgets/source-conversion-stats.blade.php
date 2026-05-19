<x-filament-widgets::widget>
    <x-filament::section heading="Conversión por web">

        <div class="space-y-4">

            @foreach ($this->getSources() as $source)
                <div class="flex items-center justify-between">

                    <div>
                        <div class="font-medium">
                            {{ $source->name }}
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ $source->vendidos_count }}
                            vendidos de
                            {{ $source->interactions_count }}
                            leads
                        </div>
                    </div>

                    <div class="text-lg font-bold">
                        {{ $source->conversion_rate }}%
                    </div>

                </div>
            @endforeach

        </div>

    </x-filament::section>
</x-filament-widgets::widget>