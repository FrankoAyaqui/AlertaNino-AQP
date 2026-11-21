// Service worker: guarda la interfaz para abrirla sin conexión.
// Los datos en vivo nunca se sirven desde aquí sin avisar: la app muestra la
// hora del último dato y el estado «Actualización no verificada».
const VERSION = "alertanino-v1";
const BASE = ["./", "index.html", "css/estilos.css", "js/app.js", "js/datos.js", "js/graficos.js", "img/icono.svg", "manifest.webmanifest"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(BASE)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (e) => {
  e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener("fetch", (e) => {
  const url = new URL(e.request.url);
  if (e.request.method !== "GET" || url.origin !== location.origin) return;
  // Red primero; si falla, la copia guardada.
  e.respondWith(
    fetch(e.request)
      .then((r) => {
        if (r.ok && !url.pathname.includes("/data/")) {
          const copia = r.clone();
          caches.open(VERSION).then((c) => c.put(e.request, copia));
        }
        return r;
      })
      .catch(() => caches.match(e.request, { ignoreSearch: true }))
  );
});

self.addEventListener("notificationclick", (e) => {
  e.notification.close();
  e.waitUntil(self.clients.matchAll({ type: "window" }).then((ws) => (ws[0] ? ws[0].focus() : self.clients.openWindow("./"))));
});
