@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <style>{!! file_get_contents(resource_path('css/filament/auth-login.css')) !!}</style>
    {{ $slot }}
</x-filament-panels::layout.base>
