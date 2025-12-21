   //NAVIDEÑO
    const overlay = document.getElementById('navidad');
    const duracion = 2000; // 2 segundos
    const cantidad = 40;


    for (let i = 0; i < cantidad; i++) {
    const snow = document.createElement('div');
    snow.className = 'snowflake';
    snow.innerHTML = '❄';


    const size = Math.random() * 10 + 10;
    snow.style.fontSize = size + 'px';
    snow.style.left = Math.random() * 100 + 'vw';
    snow.style.animationDuration = Math.random() * 1 + 1.5 + 's';
    snow.style.animationDelay = Math.random() * 0.5 + 's';


    overlay.appendChild(snow);
    }


    // Eliminar el efecto después de 2 segundos
    setTimeout(() => {
    overlay.remove();
    }, duracion);