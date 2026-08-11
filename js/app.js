document.addEventListener('DOMContentLoaded', function () {
    // Efecto de aparición suave para las tarjetas de lugares al hacer scroll.
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

    // Lightbox: click en cualquier foto marcada como clickeable la muestra en grande.
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const fotosClickeables = document.querySelectorAll('.foto-clickeable');

    if (lightbox && lightboxImg) {
        fotosClickeables.forEach((foto) => {
            foto.addEventListener('click', () => {
                lightboxImg.src = foto.src;
                lightbox.classList.remove('oculto');
            });
        });

        lightbox.addEventListener('click', () => {
            lightbox.classList.add('oculto');
            lightboxImg.src = '';
        });
    }
});