{{--
    Componente único de modal del sistema. Usar SIEMPRE <x-modal> en lugar de
    escribir a mano .modal-overlay/.modal.

    Props:
      id               (requerido) id del overlay; el JS abre por este id.
      title            título del header.
      subtitle         línea secundaria bajo el título.
      size             sm | md | lg | xl  (ancho canónico; por defecto md).
      maxWidth         ancho puntual en px (p.ej. "760px"); gana sobre `size`.
      theme            dark | gold | danger | plain  (color del header).
      open             abre el modal al renderizar.
      closeOnBackdrop  por defecto false (el sistema NO cierra al pulsar el fondo).
      closeOnEscape    por defecto false (el sistema NO cierra con Escape).
      $header / $footer slots opcionales.
--}}
@props([
    'id',
    'title' => null,
    'subtitle' => null,
    'size' => 'md',
    'maxWidth' => null,
    'theme' => 'dark',
    'closeOnBackdrop' => false,
    'closeOnEscape' => false,
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
    <div class="modal modal--{{ $size }}"
         @if($maxWidth) style="--modal-w: {{ $maxWidth }};" @endif>
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
