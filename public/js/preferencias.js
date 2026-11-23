// Se carga en <head> para aplicar tema y tamaño de letra antes de pintar la página.
(function () {
  try {
    var tema = localStorage.getItem('tema');
    if (tema === 'light' || tema === 'dark') document.documentElement.dataset.theme = tema;
    var escala = parseFloat(localStorage.getItem('escala'));
    if (escala >= 0.9 && escala <= 1.5) document.documentElement.style.setProperty('--escala', escala);
  } catch (e) { /* almacenamiento no disponible */ }
})();
