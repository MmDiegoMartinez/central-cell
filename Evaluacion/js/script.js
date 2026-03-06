const btnLeft = document.querySelector(".prev-btn"),
      btnRight = document.querySelector(".next-btn"),
      slider = document.querySelector(".project-carousel");


const images = [
    "img/1.png",
    "img/2.png",
    "img/3.png",
    "img/4.png",
    "img/5.png"
];

const links = [
    "https://drive.google.com/file/d/1Eck95QMtHn0X9IQErj0D-IoZsmgChOmF/view?usp=sharing",
    "https://drive.google.com/file/d/1m7fhNpzGkdlyUeFRnifW0wPoWs5iRRW4/view?usp=sharing",
    "https://drive.google.com/file/d/1o87nMPHLfXuusTrWyjFg6_UNrmE1Pyv1/view?usp=sharing",
    "https://drive.google.com/file/d/1xLWC4iwMQ5VTrMLdL8dy0A9b7SithPZ_/view?usp=sharing",
    "https://drive.google.com/file/d/1ELN-cSU9tDDXsNd7_U401n5zMbkvNsM5/view?usp=drivesdk"
];

// Crear dinámicamente secciones de slider sin mezclar
images.forEach((img, index) => {
    const section = document.createElement("section");
    section.classList.add("slider-section");

    const link = document.createElement("a");
    link.href = links[index];
    link.target = "_blank"; // abre en nueva pestaña

    const image = document.createElement("img");
    image.src = img;

    link.appendChild(image);
    section.appendChild(link);
    slider.appendChild(section);
});

const sliderSection = document.querySelectorAll(".slider-section");

btnLeft.addEventListener("click", () => moveToLeft());
btnRight.addEventListener("click", () => moveToRight());

let operacion = 0,
    counter = 0,
    widthImg = 100 / sliderSection.length;

function moveToRight() {
    if (counter >= sliderSection.length - 1) {
        counter = 0;
        operacion = 0;
        slider.style.transform = `translate(-${operacion}%)`;
        slider.style.transition = "none";
        return;
    }
    counter++;
    operacion += widthImg;
    slider.style.transform = `translate(-${operacion}%)`;
    slider.style.transition = "all ease .6s";
}

function moveToLeft() {
    counter--;
    if (counter < 0) {
        counter = sliderSection.length - 1;
        operacion = widthImg * (sliderSection.length - 1);
        slider.style.transform = `translate(-${operacion}%)`;
        slider.style.transition = "none";
        return;
    }
    operacion -= widthImg;
    slider.style.transform = `translate(-${operacion}%)`;
    slider.style.transition = "all ease .6s";
}
