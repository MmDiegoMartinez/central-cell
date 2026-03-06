document.addEventListener('DOMContentLoaded', () => {
    const imageContainers = document.querySelectorAll('.image-container');

    imageContainers.forEach(container => {
        container.addEventListener('mouseover', () => {
            container.querySelector('.project-description').classList.add('show');
        });

        container.addEventListener('mouseout', () => {
            container.querySelector('.project-description').classList.remove('show');
        });
    });
});

