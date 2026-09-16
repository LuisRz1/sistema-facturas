@props([
    'id',
    'title' => null,
    'subtitle' => null,
    'maxWidth' => '680px',
    'theme' => 'dark',
    'closeOnBackdrop' => true,
    'closeOnEscape' => true,
    'open' => false,
])

<div class="modal-overlay{{ $open ? ' open' : '' }}"
     id="{{ $id }}"
     data-modal
     data-close-on-backdrop="{{ $closeOnBackdrop ? '1' : '0' }}"
     data-close-on-escape="{{ $closeOnEscape ? '1' : '0' }}"
     role="dialog"
     aria-modal="true"
     @if($title) aria-labelledby="{{ $id }}-title" @endif
     aria-hidden="{{ $open ? 'false' : 'true' }}">
    <div class="modal" style="max-width: {{ $maxWidth }};">
        @if($title || isset($header))
            <div class="modal-header{{ $theme !== 'dark' ? ' modal-header--' . $theme : '' }}">
                <div class="modal-titles">
                    @if($title)
                        <h2 id="{{ $id }}-title">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p>{{ $subtitle }}</p>
                    @endif
                    {{ $header ?? '' }}
                </div>
                <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">&times;</button>
            </div>
        @endif

        <div class="modal-body">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="modal-footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
