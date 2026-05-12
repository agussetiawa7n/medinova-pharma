<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Save Images
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
