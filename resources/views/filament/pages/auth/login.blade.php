<x-filament-panels::page.simple>
    <div class="flex flex-col items-center mb-8">
        <img
            src="{{ asset('images/masias-logo.svg') }}"
            alt="Masias Logo"
            class="w-[300px] h-[100px] object-contain"
        />
        <p class="mt-3 text-sm font-semibold tracking-[0.25em] uppercase text-primary-400">
            MASIAS CMS
        </p>
    </div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            size="xl"
            class="w-full"
        >
            {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page.simple>
