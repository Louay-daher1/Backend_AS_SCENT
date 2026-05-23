@php
    $heading ??= $this->getHeading();
    $subheading ??= $this->getSubHeading();
@endphp

<div class="auth-login-root">
    <div class="auth-split">
        <aside class="auth-split-brand" aria-hidden="true">
            <div class="auth-split-brand-inner">
                <img
                    src="{{ asset('images/brand-logo.png') }}"
                    alt="{{ filament()->getBrandName() }}"
                    class="auth-split-logo"
                />
                <p class="auth-split-brand-name">{{ filament()->getBrandName() }}</p>
                <p class="auth-split-brand-tagline">Perfume administration</p>
            </div>
        </aside>

        <main class="auth-split-form-panel">
            <div class="auth-split-form-inner">
                <img
                    src="{{ asset('images/brand-logo.png') }}"
                    alt="{{ filament()->getBrandName() }}"
                    class="auth-split-mobile-logo"
                />
                @if (filled($heading))
                    <header class="auth-split-form-header">
                        <h1 class="auth-split-form-heading">{{ $heading }}</h1>
                        @if (filled($subheading))
                            <p class="auth-split-form-subheading">{{ $subheading }}</p>
                        @endif
                    </header>
                @endif

                {{ $this->content }}
            </div>
        </main>
    </div>

    @if (! $this instanceof \Filament\Tables\Contracts\HasTable)
        <x-filament-actions::modals />
    @endif
</div>
