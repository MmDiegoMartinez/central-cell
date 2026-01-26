const hoy = new Date();
const dia = hoy.getDate();
const mes = hoy.getMonth() + 1;

/* Fechas */
const esNavidad = (mes === 12 && dia >= 16) || (mes === 1 && dia <= 7);
const esMuertos = (mes === 10 && dia >= 29) || (mes === 11 && dia <= 3);
const esIndependencia = (mes === 9 && dia >= 14 && dia <= 17);

/* Navidad */
if (esNavidad) {
    const navidad = document.getElementById("navidad");
    if (navidad) {
        const frag = document.createDocumentFragment();

        for (let i = 0; i < 40; i++) {
            const snow = document.createElement("div");
            snow.className = "snowflake";
            snow.textContent = "❄";
            snow.style.cssText = `
                font-size: ${Math.random() * 12 + 10}px;
                left: ${Math.random() * 100}vw;
                animation-duration: ${Math.random() * 2 + 2}s;
            `;
            frag.appendChild(snow);
        }

        navidad.appendChild(frag);
    }
} else {
    const navidad = document.getElementById("navidad");
    if (navidad) navidad.classList.add("oculto");
}

/* Muertos */
if (esMuertos) {
    const muertos = document.getElementById("muertos");
    if (muertos) {
        const frag = document.createDocumentFragment();

        for (let i = 0; i < 25; i++) {
            const p = document.createElement("div");
            p.className = "petalo";
            p.style.cssText = `
                left: ${Math.random() * 100}vw;
                animation-duration: ${Math.random() * 1 + 2}s;
                animation-delay: ${Math.random() * 3}s;
            `;
            frag.appendChild(p);
        }

        muertos.appendChild(frag);
    }
} else {
    const muertos = document.getElementById("muertos");
    if (muertos) muertos.classList.add("oculto");
}

/* Independencia */
if (esIndependencia) {
    const independencia = document.getElementById("independencia");
    if (independencia) {
        const colores = ["#006847", "#ffffff", "#ce1126"];
        const frag = document.createDocumentFragment();

        for (let i = 0; i < 30; i++) {
            const c = document.createElement("div");
            c.className = "confeti";
            c.style.cssText = `
                left: ${Math.random() * 100}vw;
                background: ${colores[Math.floor(Math.random() * 3)]};
                animation-duration: ${Math.random() * 2 + 2.5}s;
                animation-delay: ${Math.random() * 3}s;
            `;
            frag.appendChild(c);
        }

        independencia.appendChild(frag);
    }
} else {
    const independencia = document.getElementById("independencia");
    if (independencia) independencia.classList.add("oculto");
}