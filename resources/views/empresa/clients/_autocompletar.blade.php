{{-- Trae la ficha de SUNAT o RENIEC al terminar de escribir el documento.

     Va como x-data de los dos formularios (pagina y modal) para no tener dos
     copias que se separen. Se incluye dentro del atributo, asi que aqui solo
     puede haber el objeto, sin etiquetas ni <script>: el modal se carga por
     AJAX y un script inyectado asi no siempre llega a ejecutarse. --}}
{
    buscando: false,
    aviso: '',
    avisoTipo: '',

    /* Cuantas cifras tiene el documento, o 0 si no se puede consultar.
       El carne de extranjeria (4) y el no domiciliado (0) no estan en ningun
       padron que podamos mirar. */
    largo(tipo) {
        return tipo === '6' ? 11 : (tipo === '1' ? 8 : 0);
    },

    async buscar(tipo, numero) {
        const n = String(numero || '').replace(/\D/g, '');
        const largo = this.largo(tipo);

        this.aviso = '';
        this.avisoTipo = '';

        /* Mientras se escribe no se molesta: solo al estar completo. */
        if (! largo || n.length !== largo) return;

        this.buscando = true;

        try {
            const res = await fetch('{{ url('/empresa/clients-consultar') }}/' + tipo + '/' + n, {
                headers: { 'Accept': 'application/json' },
            });

            if (res.status === 429) {
                this.aviso = 'Demasiadas consultas seguidas. Espera un momento.';
                this.avisoTipo = 'error';
                return;
            }

            const d = await res.json();

            if (! d.encontrado) {
                this.aviso = d.mensaje;
                this.avisoTipo = 'error';
                return;
            }

            /* Se rellena, no se bloquea: los datos del padron pueden estar
               viejos y el usuario tiene que poder corregirlos. */
            if (this.$refs.razon) this.$refs.razon.value = d.razon_social;
            if (this.$refs.direccion && d.direccion) this.$refs.direccion.value = d.direccion;

            /* Un RUC de baja o no habido se puede facturar mal, asi que se
               avisa en vez de rellenar en silencio. */
            const ojo = [d.estado, d.condicion].filter(v => v && v !== 'ACTIVO' && v !== 'HABIDO');

            this.aviso = ojo.length ? 'Encontrado, pero figura como ' + ojo.join(' y ') + '.' : 'Datos traídos del padrón.';
            this.avisoTipo = ojo.length ? 'ojo' : 'ok';
        } catch (e) {
            this.aviso = 'No se pudo consultar. Escribe los datos a mano.';
            this.avisoTipo = 'error';
        } finally {
            this.buscando = false;
        }
    },
}
