<x-filament::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex justify-end gap-3">
            <x-filament::button wire:click="testConnection" color="gray">
                Test Connection
            </x-filament::button>
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
