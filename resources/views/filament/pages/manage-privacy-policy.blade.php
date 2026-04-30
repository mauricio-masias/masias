<x-filament-panels::page>
    <div>
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button wire:click="save" wire:loading.attr="disabled">
                Save Changes
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
