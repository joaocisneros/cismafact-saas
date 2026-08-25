{{--
    Abre el detalle del comprobante recien emitido en el mismo modal que usa el
    boton "Ver" de la tabla, en vez de mandar al usuario a otra pantalla.

    El controlador deja en sesion 'abrir_documento' (el id) y 'abrir_titulo'.
    Espera: $ruta = nombre de la ruta show del modulo.
--}}
@if(session('abrir_documento'))
    <script>
        (function () {
            const url = @js(route($ruta, session('abrir_documento')));
            const titulo = @js(session('abrir_titulo', 'Detalle'));

            // openAdminModal la define Alpine en su init(), asi que puede no
            // existir todavia cuando este script corre: se reintenta un momento.
            let intentos = 0;
            const abrir = () => {
                if (typeof window.openAdminModal === 'function') {
                    window.openAdminModal(url, titulo);
                    return;
                }
                if (++intentos < 60) setTimeout(abrir, 50);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', abrir);
            } else {
                abrir();
            }
        })();
    </script>
@endif
