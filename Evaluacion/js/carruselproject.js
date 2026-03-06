const carousel = document.querySelector('.project-carousel');
const projectCards = document.querySelectorAll('.project-card');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');

let currentIndex = 0;

function showSlide(index) {
    const projectWidth = document.querySelector('.project-card').offsetWidth;
    carousel.style.transform = `translateX(-${index * projectWidth}px)`;
    
    // Mostrar 3 proyectos visibles (ajusta este número si quieres más)
    projectCards.forEach((card, i) => {
        if (i >= index && i < index + 3) {
            card.classList.add('visible');
        } else {
            card.classList.remove('visible');
        }
    });
}

nextBtn.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % projectCards.length;
    showSlide(currentIndex);
});

prevBtn.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + projectCards.length) % projectCards.length;
    showSlide(currentIndex);
});

document.addEventListener("DOMContentLoaded", () => {
  projectCards.forEach((card, index) => {
    setTimeout(() => {
      card.classList.add("show");
    }, index * 200); // efecto escalonado
  });
});

// Mostrar al inicio
showSlide(currentIndex);
