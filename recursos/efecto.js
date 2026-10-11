const hoy = new Date();
const dia = hoy.getDate();
const mes = hoy.getMonth() + 1;

/* Fechas */
const esNavidad = (mes === 12 && dia >= 16) || (mes === 1 && dia <= 7);
const esMuertos = (mes === 10 && dia >= 11) || (mes === 11 && dia <= 3);
//const esIndependencia = (mes === 9 && dia >= 14 && dia <= 17);
const esIndependencia = (mes === 8 && dia >= 10) || (mes === 9 && dia <= 18);

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


/* ═══════════════════════════════════════════════════════════
   CALACA DE CUERPO COMPLETO — ilustración SVG + rig por articulaciones
   Cada hueso se dibuja desde su articulación (origen 0,0 del grupo),
   así los brazos giran desde el hombro, el codo y la muñeca sin
   separaciones ni deformaciones.
═══════════════════════════════════════════════════════════ */
function construirCalaca() {
    const O = '#2a1710', H = '#f6eedb', SH = '#d6c093', DK = '#1c0f09';
    const NA = '#ff9f1c', NB = '#ffb703', NC = '#b34700', TQ = '#1aa6a0', VD = '#5b8f3a';
    const n = v => Math.round(v * 100) / 100;
    const OUT = 2.6;                                   // grosor del contorno

    /* ── primitivas: círculo, cápsula, polígono, trazo, rectángulo ── */
    const c = (x, y, r) => ({ t: 'c', x, y, r });
    const l = (x1, y1, x2, y2, w) => ({ t: 'l', x1, y1, x2, y2, w });
    const p = d => ({ t: 'p', d });
    const s = (d, w) => ({ t: 's', d, w });
    const r = (x, y, w, h, rx = 2) => ({ t: 'r', x, y, w, h, rx });

    const pasada = (it, borde) => {
        const col = borde ? O : H;
        const ex = borde ? OUT : 0;
        const st = borde ? ` stroke="${O}" stroke-width="${OUT}" stroke-linejoin="round"` : '';
        switch (it.t) {
            case 'c': return `<circle cx="${n(it.x)}" cy="${n(it.y)}" r="${n(it.r)}" fill="${col}"${st}/>`;
            case 'p': return `<path d="${it.d}" fill="${col}"${st}/>`;
            case 'r': return `<rect x="${n(it.x)}" y="${n(it.y)}" width="${n(it.w)}" height="${n(it.h)}" rx="${it.rx}" fill="${col}"${st}/>`;
            case 'l': return `<line x1="${n(it.x1)}" y1="${n(it.y1)}" x2="${n(it.x2)}" y2="${n(it.y2)}" stroke="${col}" stroke-width="${n(it.w + ex)}" stroke-linecap="round"/>`;
            case 's': return `<path d="${it.d}" fill="none" stroke="${col}" stroke-width="${n(it.w + ex)}" stroke-linecap="round" stroke-linejoin="round"/>`;
        }
    };
    /* silueta unificada: primero todos los bordes, luego todos los rellenos
       → las piezas de un mismo hueso se funden y solo queda el contorno exterior */
    const hueso = (items, detalle = '') =>
        `<g>${items.map(i => pasada(i, true)).join('')}${items.map(i => pasada(i, false)).join('')}${detalle}</g>`;
    const sombra = (d, w = 1.2) => `<path d="${d}" fill="none" stroke="${SH}" stroke-width="${w}" stroke-linecap="round"/>`;

    /* ── hueso largo: diáfisis con cintura + dos cóndilos por extremo ── */
    const largo = (len, w, r1, r2 = r1, ox = 0, oy = 0) => [
        p(`M${ox - w / 2} ${oy + r1 * .5} Q${ox - w * .3} ${oy + len / 2} ${ox - w / 2} ${oy + len - r2 * .5} ` +
          `L${ox + w / 2} ${oy + len - r2 * .5} Q${ox + w * .3} ${oy + len / 2} ${ox + w / 2} ${oy + r1 * .5}Z`),
        c(ox - r1 * .55, oy + r1 * .45, r1 * .8), c(ox + r1 * .55, oy + r1 * .45, r1 * .8),
        c(ox - r2 * .55, oy + len - r2 * .45, r2 * .8), c(ox + r2 * .55, oy + len - r2 * .45, r2 * .8)
    ];

    /* ── dedo articulado: metacarpo + falanges, con nudillos ── */
    const dedo = (bx, by, ang, segs, ws, sg, k = 1) => {
        const it = []; let x = bx, y = by, a = ang;
        segs.forEach((L, i) => {
            const rad = a * Math.PI / 180;
            const x2 = x + sg * Math.sin(rad) * L, y2 = y + Math.cos(rad) * L;
            it.push(l(x, y, x2, y2, ws[i]));
            if (i < segs.length - 1) it.push(c(x2, y2, ws[i] * .5 + .85 * k));
            x = x2; y = y2; a += ang * .08;
        });
        return it;
    };
    /* mano abierta, apuntando hacia +y; sg = +1 pulgar hacia +x */
    const mano = sg => hueso([
        c(-4.6 * sg, 3, 2.7), c(-1.6 * sg, 2.4, 2.9), c(1.6 * sg, 2.8, 2.9), c(4.4 * sg, 3.8, 2.6), c(0, 5.4, 2.6),
        ...dedo(sg * 4.6, 5, 34, [6.5, 6, 4.5], [3, 2.7, 2.3], sg),                       // pulgar
        ...dedo(sg * 3.2, 6.5, 6, [9.5, 7, 4.8, 3.6], [2.7, 2.3, 2, 1.8], sg),            // índice
        ...dedo(sg * .4, 7, .5, [10.5, 7.6, 5, 4], [2.8, 2.4, 2.1, 1.9], sg),             // medio
        ...dedo(sg * -2.4, 6.8, -5.5, [9.6, 7, 4.6, 3.6], [2.7, 2.3, 2, 1.8], sg),        // anular
        ...dedo(sg * -4.8, 6, -12, [8.4, 5.6, 3.6, 3], [2.4, 2.1, 1.8, 1.6], sg)          // meñique
    ]);
    /* pie visto de frente; lat = lado lateral (+1 hacia +x) */
    const pie = lat => hueso([
        c(0, 3.2, 4.8), c(lat * -3, 8.2, 3.4), c(lat * 2.8, 8.2, 3.6), c(lat * 6, 9.4, 2.8),
        ...dedo(lat * -4.6, 10, -4, [11, 7], [4.2, 3.6], lat, .5),
        ...dedo(lat * -1.8, 10.4, 1, [12, 4.4, 3], [3.2, 2.6, 2.2], lat, .5),
        ...dedo(lat * 1, 10.4, 5, [11.5, 4, 3], [3.2, 2.5, 2.1], lat, .5),
        ...dedo(lat * 3.7, 10.4, 10, [10.5, 3.8, 2.6], [3, 2.4, 2], lat, .5),
        ...dedo(lat * 6.2, 10, 17, [9.5, 3.4], [2.8, 2.2], lat, .5)
    ]);

    /* ── flor de cempasúchil ── */
    const flor = (x, y, rad, rot = 0) => {
        let pet = '';
        for (let i = 0; i < 9; i++) {
            pet += `<ellipse cx="0" cy="${n(-rad * .55)}" rx="${n(rad * .3)}" ry="${n(rad * .5)}" transform="rotate(${n(rot + i * 40)})" fill="${i % 2 ? NB : NA}" stroke="#8a3b00" stroke-width=".7"/>`;
        }
        return `<g transform="translate(${n(x)} ${n(y)})"><circle r="${n(rad * 1.02)}" fill="${NC}"/>${pet}<circle r="${n(rad * .3)}" fill="#8a3b00"/><circle r="${n(rad * .13)}" fill="#ffd27a"/></g>`;
    };
    const hoja = (x, y, ang) => `<ellipse cx="${n(x)}" cy="${n(y)}" rx="4.2" ry="1.9" transform="rotate(${n(ang)} ${n(x)} ${n(y)})" fill="${VD}" stroke="#2f5a1f" stroke-width=".7"/>`;
    const polar = (cx, cy, R, deg) => [cx + R * Math.cos(deg * Math.PI / 180), cy + R * Math.sin(deg * Math.PI / 180)];
    const elipse = (cx, cy, rx, ry) => `M${cx - rx} ${cy} a${rx} ${ry} 0 1 0 ${2 * rx} 0 a${rx} ${ry} 0 1 0 ${-2 * rx} 0Z`;

    /* ═════════════ CRÁNEO ═════════════ */
    const ojo = (cx, dirInterior) => {
        let ticks = '';
        for (let a = 0; a < 360; a += 30) {
            const rad = a * Math.PI / 180;
            const cx2 = Math.cos(rad) * dirInterior;
            if (cx2 > .45 || cx2 < -.8) continue;                      // sin adornos hacia el centro ni sobre el borde
            const x1 = cx + Math.cos(rad) * 9.2, y1 = 30.5 + Math.sin(rad) * 10.2;
            const x2 = cx + Math.cos(rad) * 11.4, y2 = 30.5 + Math.sin(rad) * 12.4;
            ticks += `<line x1="${n(x1)}" y1="${n(y1)}" x2="${n(x2)}" y2="${n(y2)}" stroke="${(a / 30) % 2 ? TQ : NA}" stroke-width="1.5" stroke-linecap="round"/>`;
        }
        return `<ellipse cx="${cx}" cy="30.5" rx="6.4" ry="7.2" fill="${DK}"/>` +
               `<ellipse cx="${cx}" cy="30.5" rx="8" ry="9" fill="none" stroke="${NA}" stroke-width="1.5"/>` + ticks +
               `<circle cx="${cx + dirInterior * 1.2}" cy="31.5" r="1.6" fill="${NB}"/><circle cx="${cx + dirInterior * 1.2}" cy="31.5" r=".7" fill="#fff6d0"/>`;
    };
    const dientes = (x0, y0, w, h, k, extra = '') => {
        let div = '';
        for (let i = 1; i < k; i++) div += `<line x1="${n(x0 + w / k * i)}" y1="${y0}" x2="${n(x0 + w / k * i)}" y2="${y0 + h}" stroke="${O}" stroke-width=".8"/>`;
        return `<rect x="${x0}" y="${y0}" width="${w}" height="${h}" rx="1.6" fill="#fffaf0" stroke="${O}" stroke-width="1"/>${div}${extra}`;
    };

    const craneo = () => {
        let corona = '';
        const ang = [198, 234, 270, 306, 342], rad = [5.4, 6.8, 7.6, 6.8, 5.4];
        [216, 252, 288, 324].forEach(a => { const [x, y] = polar(100, 26, 23.2, a); corona += hoja(x, y, a + 90); });
        ang.forEach((a, i) => { const [x, y] = polar(100, 26, 19.8, a); corona += flor(x, y, rad[i], i * 17); });
        return (
            /* cabeza (cráneo + maxilar) */
            hueso([p(elipse(100, 26, 19.5, 20)), p('M81 32 C80 42 83 47 87 50 L113 50 C117 47 120 42 119 32Z')],
                  sombra('M83.5 37 Q84.5 45 89.5 48.2') + sombra('M116.5 37 Q115.5 45 110.5 48.2') +
                  sombra('M84 18 Q81 26 83 34', 1.1) + sombra('M116 18 Q119 26 117 34', 1.1)) +
            `<rect x="88" y="46.5" width="24" height="7.5" rx="3" fill="${DK}"/>` +
            /* mandíbula (se mueve) */
            `<g class="c-mandibula">` +
            hueso([p('M85.5 48 L114.5 48 C115 54 112 59.5 106.5 62 Q100 64 93.5 62 C88 59.5 85 54 85.5 48Z')],
                  sombra('M89 58.5 Q100 63 111 58.5', 1.1)) +
            dientes(91, 50.8, 18, 4.6, 6) + `</g>` +
            dientes(89.5, 45.4, 21, 4.9, 7) +
            /* cuencas, nariz y adornos */
            ojo(91, 1) + ojo(109, -1) +
            `<path d="M100 36 C96.8 39.3 95.2 43.6 97.7 45.2 L100 43 L102.3 45.2 C104.8 43.6 103.2 39.3 100 36Z" fill="${DK}"/>` +
            `<circle cx="100" cy="17.2" r="1.5" fill="${TQ}"/><circle cx="93.4" cy="19" r="1.2" fill="${NA}"/><circle cx="106.6" cy="19" r="1.2" fill="${NA}"/>` +
            `<circle cx="95" cy="57.6" r=".9" fill="${TQ}"/><circle cx="100" cy="59.4" r=".9" fill="${NA}"/><circle cx="105" cy="57.6" r=".9" fill="${TQ}"/>` +
            flor(85.4, 44, 2.9) + flor(114.6, 44, 2.9) +
            corona
        );
    };

    /* ═════════════ TRONCO ═════════════ */
    const vertebras = () => {
        const it = [];
        [57.5, 62.5, 67.5].forEach(y => it.push(r(95.8, y, 8.4, 4.4, 2)));                     // cervicales
        for (let i = 0; i < 11; i++) it.push(r(94.8, 73 + i * 6, 10.4, 5, 2));                 // torácicas
        for (let i = 0; i < 4; i++) it.push(r(93.4, 138.5 + i * 6.6, 13.2, 5.8, 2.4));         // lumbares
        let det = '';
        for (let i = 0; i < 11; i++) det += sombra(`M96.5 ${75.8 + i * 6} H103.5`, .8);
        return hueso(it, det);
    };
    const costillas = () => {
        const hw = [22, 27, 30, 30, 27, 22], ys = [76, 83.6, 91.2, 98.8, 106.4, 114], dr = [7, 11, 15, 18, 20, 20];
        let huesos = '';
        for (let i = 5; i >= 0; i--) {                              // de abajo hacia arriba: las superiores quedan encima
            [1, -1].forEach(sg => {
                const w = hw[i], y = ys[i], d = dr[i];
                huesos += hueso([s(`M${sg * 5} ${y} C${sg * (5 + (w - 5) * .5)} ${y - 3.5} ${sg * w} ${y + d * .25} ${sg * w} ${y + d} Q${sg * w} ${y + d + 4} ${sg * (w - 3.5)} ${y + d + 6.5}`, 3.4)],
                    `<path d="M${sg * 8} ${y - 1.4} C${sg * (5 + (w - 5) * .5)} ${y - 4.2} ${sg * (w - 1.4)} ${y + d * .25} ${sg * (w - 1.2)} ${y + d * .8}" fill="none" stroke="${SH}" stroke-width=".9" stroke-linecap="round"/>`);
            });
        }
        return `<ellipse cx="100" cy="106" rx="26.5" ry="32" fill="${DK}"/>` + vertebras() + `<g transform="translate(100 0)">${huesos.replace(/<g>/g, '<g>')}</g>`;
    };
    const clavículas = () => hueso([
        s('M104 73.5 Q86 69 65 79', 3.4), s('M96 73.5 Q114 69 135 79', 3.4)
    ], sombra('M100 71.5 Q86 68 70 75', 1) + sombra('M100 71.5 Q114 68 130 75', 1));

    const pelvis = () => hueso([
        p('M100 160 C90 156 72 156 67 166 C64 176 72 186 82 190 L90 196 Q95 198 98 192 L100 188 L102 192 Q105 198 110 196 L118 190 C128 186 136 176 133 166 C128 156 110 156 100 160Z')
    ], sombra('M96 159 C84 156 72 159 69 168', 1.3) + sombra('M104 159 C116 156 128 159 131 168', 1.3) +
       [74, 79, 84, 89].map((x, i) => `<circle cx="${x}" cy="${[165.2, 162.4, 161.2, 161.2][i]}" r="1" fill="${i % 2 ? TQ : NA}"/><circle cx="${200 - x}" cy="${[165.2, 162.4, 161.2, 161.2][i]}" r="1" fill="${i % 2 ? TQ : NA}"/>`).join('') +
       `<ellipse cx="86.5" cy="181" rx="4.4" ry="5.6" transform="rotate(18 86.5 181)" fill="${DK}"/><ellipse cx="113.5" cy="181" rx="4.4" ry="5.6" transform="rotate(-18 113.5 181)" fill="${DK}"/>`) +
        hueso([p('M93.5 160 L106.5 160 L103 181 L100 186 L97 181Z')],
              `<path d="M100 163 V183" stroke="${SH}" stroke-width=".9"/><circle cx="98" cy="170" r=".8" fill="${DK}"/><circle cx="102" cy="170" r=".8" fill="${DK}"/><circle cx="99" cy="176" r=".7" fill="${DK}"/><circle cx="101" cy="176" r=".7" fill="${DK}"/>`);

    const collar = () => {
        let fl = '', hilo = 'M71 76 Q100 92 129 76';
        for (let i = 0; i <= 6; i++) {
            const t = i / 6, x = (1 - t) * (1 - t) * 71 + 2 * (1 - t) * t * 100 + t * t * 129;
            const y = (1 - t) * (1 - t) * 76 + 2 * (1 - t) * t * 90 + t * t * 76;
            fl += flor(x, y + 1, i === 3 ? 4.6 : 3.4, i * 23);
        }
        return `<path d="${hilo}" fill="none" stroke="#8a3b00" stroke-width="1.1"/>${fl}`;
    };

    /* ═════════════ BRAZOS (hombro → codo → muñeca) ═════════════ */
    const brazo = lado => {                       // lado: 'izq' o 'der' (de pantalla)
        const sg = lado === 'izq' ? -1 : 1;       // +1: pulgar hacia +x
        const x = lado === 'izq' ? 66 : 134;
        return `<g transform="translate(${x} 80)"><g class="c-brazo-${lado}">` +
            hueso(largo(56, 6.4, 4.8, 4.4), sombra(`M${sg * 1.6} 8 V50`, 1)) +
            `<g transform="translate(0 53.5)"><g class="c-ant-${lado}">` +
                hueso([...largo(49, 4.4, 3.8, 3.2, -2.5 * sg), ...largo(46, 3.8, 3.2, 3.9, 2.7 * sg, 3)]) +
                `<g transform="translate(0 47.5)"><g class="c-mano-${lado}">${mano(sg)}</g></g>` +
            `</g></g></g></g>`;
    };

    /* ═════════════ PIERNAS (cadera → rodilla → tobillo) ═════════════ */
    const pierna = lado => {
        const sm = lado === 'izq' ? 1 : -1;       // +1: medial hacia +x
        const x = lado === 'izq' ? 85.5 : 114.5;
        return `<g transform="translate(${x} 184) rotate(${-2.2 * sm})">` +
            hueso(largo(76, 8.2, 6.6, 7), sombra(`M${-sm * 2} 12 V66`, 1.1)) +
            `<g transform="translate(0 74) rotate(${2.2 * sm})">` +
                hueso([...largo(70, 6.8, 6.4, 5.2), ...largo(66, 3.4, 3, 3.6, -sm * 4.8, 3)]) +
                hueso([c(0, 0.5, 4)], `<path d="M-2 -1.5 Q0 -3 2 -1.5" fill="none" stroke="${SH}" stroke-width="1"/>`) +
                `<g transform="translate(0 66) rotate(${sm * 11})">${pie(-sm)}</g>` +
            `</g></g>`;
    };

    /* ═════════════ ENSAMBLE (de atrás hacia adelante) ═════════════ */
    return `<svg viewBox="-34 -12 234 374" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">` +
        pierna('izq') + pierna('der') + pelvis() +
        `<g class="c-torso">` +
            costillas() + clavículas() + collar() +
            brazo('der') + brazo('izq') +
            `<g class="c-cabeza"><g transform="translate(100 56) scale(.9) translate(-100 -56)">${craneo()}</g></g>` +
        `</g></svg>`;
}


/* ═══════════════════════════════════════════════════════════
   Muertos — la calaca asoma, saluda y se va (~4 s)
   (reemplaza el bloque "if (esMuertos) {...} else {...}" de efecto.js;
    la función construirCalaca va arriba, en el mismo archivo)
═══════════════════════════════════════════════════════════ */
if (esMuertos) {
    const muertos = document.getElementById("muertos");
    if (muertos) {
        muertos.innerHTML = '<div class="calaca">' + construirCalaca() + '</div>';
        setTimeout(() => { muertos.innerHTML = ""; }, 4300);   // termina sola y se limpia
    }
} else {
    const muertos = document.getElementById("muertos");
    if (muertos) muertos.classList.add("oculto");
}

/* Muertos — pétalos de cempasúchil (bloque del efecto.js) */
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