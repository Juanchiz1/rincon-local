document.addEventListener('DOMContentLoaded', function () {
    // --- Animación de aparición para las tarjetas del listado ---
    const tarjetas = document.querySelectorAll('.tarjeta-lugar');
    if (tarjetas.length > 0) {
        const observador = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('visible');
                    observador.unobserve(entrada.target);
                }
            });
        }, { threshold: 0.1 });

        tarjetas.forEach((tarjeta) => {
            tarjeta.classList.add('antes-de-aparecer');
            observador.observe(tarjeta);
        });
    }

    // --- Lightbox ---
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');

    function activarLightbox(elemento) {
        if (!lightbox || !lightboxImg) return;
        elemento.addEventListener('click', () => {
            lightboxImg.src = elemento.src;
            lightbox.classList.remove('oculto');
        });
    }

    document.querySelectorAll('.foto-clickeable').forEach(activarLightbox);

    if (lightbox) {
        lightbox.addEventListener('click', () => {
            lightbox.classList.add('oculto');
            lightboxImg.src = '';
        });
    }

    // --- Geolocalización: "lugares cerca de mí" ---
    // (Este bloque va ANTES del bloque de AJAX a propósito: el bloque de
    // AJAX tiene un "return" temprano cuando no está en lugar.php, y eso
    // cortaría la ejecución antes de llegar aquí si lo dejáramos después.)
    const btnCercaMi = document.getElementById('btn-cerca-mi');
    const mensajeGeo = document.getElementById('mensaje-geo');

    if (btnCercaMi) {
        btnCercaMi.addEventListener('click', () => {
            if (!navigator.geolocation) {
                mensajeGeo.textContent = 'Tu navegador no soporta geolocalización.';
                return;
            }

            btnCercaMi.disabled = true;
            btnCercaMi.textContent = 'Buscando tu ubicación...';
            mensajeGeo.textContent = '';

            navigator.geolocation.getCurrentPosition(
                (posicion) => {
                    const lat = posicion.coords.latitude;
                    const lng = posicion.coords.longitude;
                    window.location.href = `index.php?lat=${lat}&lng=${lng}`;
                },
                (error) => {
                    btnCercaMi.disabled = false;
                    btnCercaMi.textContent = '📍 Lugares cerca de mí';

                    if (error.code === error.PERMISSION_DENIED) {
                        mensajeGeo.textContent = 'Necesitas permitir el acceso a tu ubicación para usar esta función.';
                    } else {
                        mensajeGeo.textContent = 'No se pudo obtener tu ubicación, intenta de nuevo.';
                    }
                }
            );
        });
    }

    // --- Búsqueda en vivo ---
    const buscador = document.getElementById('buscador-lugares');
    if (buscador) {
        const tarjetasBuscables = document.querySelectorAll('.tarjeta-lugar');
        const mensajeSinResultados = document.getElementById('mensaje-sin-resultados');

        buscador.addEventListener('input', () => {
            const termino = buscador.value.trim().toLowerCase();
            let visibles = 0;

            tarjetasBuscables.forEach((tarjeta) => {
                const coincide = tarjeta.dataset.nombre.includes(termino)
                    || tarjeta.dataset.categoria.includes(termino);

                tarjeta.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            if (mensajeSinResultados) {
                mensajeSinResultados.classList.toggle('oculto', visibles > 0);
            }
        });
    }

    // --- Envío de reseñas por AJAX ---
    const formularioResena = document.getElementById('formulario-resena');
    if (!formularioResena) return; // Estamos en index.php, no hay nada más que hacer.

    const mensajeResena = document.getElementById('mensaje-resena');
    const contenedorResenas = document.getElementById('contenedor-resenas');
    const botonEnviar = formularioResena.querySelector('button[type="submit"]');

    function crearTarjetaResena(resena) {
        const article = document.createElement('article');
        article.className = 'tarjeta-resena';

        const cabecera = document.createElement('div');
        cabecera.className = 'cabecera-resena';

        const autorEl = document.createElement('strong');
        autorEl.textContent = resena.autor;
        cabecera.appendChild(autorEl);

        if (resena.emoji) {
            const emojiEl = document.createElement('span');
            emojiEl.className = 'emoji-resena';
            emojiEl.textContent = resena.emoji;
            cabecera.appendChild(emojiEl);
        }
        article.appendChild(cabecera);

        const detalle = document.createElement('div');
        detalle.className = 'detalle-ratings';
        const estrellas = (n) => '★'.repeat(n) + '☆'.repeat(5 - n);
        ['comida', 'servicio', 'ambiente'].forEach((categoria) => {
            const span = document.createElement('span');
            const etiqueta = categoria.charAt(0).toUpperCase() + categoria.slice(1);
            span.textContent = `${etiqueta}: ${estrellas(resena['rating_' + categoria])}`;
            detalle.appendChild(span);
        });
        article.appendChild(detalle);

        if (resena.imagen) {
            const img = document.createElement('img');
            img.src = resena.imagen;
            img.alt = 'Foto de la reseña';
            img.className = 'foto-resena foto-clickeable';
            article.appendChild(img);
            activarLightbox(img);
        }

        if (resena.comentario) {
            const p = document.createElement('p');
            p.className = 'comentario-resena';
            p.textContent = resena.comentario;
            article.appendChild(p);
        }

        const time = document.createElement('time');
        time.textContent = resena.fecha;
        article.appendChild(time);

        return article;
    }

    formularioResena.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        botonEnviar.disabled = true;
        botonEnviar.textContent = 'Enviando...';
        mensajeResena.innerHTML = '';

        try {
            const datosFormulario = new FormData(formularioResena);
            const respuesta = await fetch('api/guardar_resena.php', {
                method: 'POST',
                body: datosFormulario,
            });
            const datos = await respuesta.json();

            if (!datos.exito) {
                const lista = document.createElement('ul');
                lista.className = 'mensaje-error';
                datos.errores.forEach((error) => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    lista.appendChild(li);
                });
                mensajeResena.appendChild(lista);
                return;
            }

            const mensajeSinResenas = document.getElementById('mensaje-sin-resenas');
            if (mensajeSinResenas) mensajeSinResenas.remove();

            contenedorResenas.prepend(crearTarjetaResena(datos.resena));

            document.getElementById('conteo-resenas').textContent = datos.total_resenas;
            const conteoCabecera = document.getElementById('conteo-resenas-cabecera');
            if (conteoCabecera) conteoCabecera.textContent = datos.total_resenas;

            document.getElementById('valor-promedio-general').textContent = datos.promedio_general;
            document.getElementById('promedio-grande').style.display = '';

            ['comida', 'servicio', 'ambiente'].forEach((categoria) => {
                const valor = datos.promedios_categoria[categoria];
                document.getElementById('barra-' + categoria).style.width = (valor / 5 * 100) + '%';
                document.getElementById('valor-' + categoria).textContent = valor;
            });
            document.getElementById('resumen-calificaciones').style.display = '';

            const exito = document.createElement('p');
            exito.className = 'mensaje-exito';
            exito.textContent = '¡Gracias por tu reseña!';
            mensajeResena.appendChild(exito);

            formularioResena.reset();
        } catch (error) {
            const aviso = document.createElement('p');
            aviso.className = 'mensaje-error';
            aviso.textContent = 'Ocurrió un error de conexión, intenta de nuevo.';
            mensajeResena.appendChild(aviso);
        } finally {
            botonEnviar.disabled = false;
            botonEnviar.textContent = 'Publicar reseña';
        }
    });
});