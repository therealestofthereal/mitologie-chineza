const lightbox = document.createElement('div');
lightbox.id = 'lightbox';
lightbox.className = 'lightbox';
lightbox.innerHTML = `
  <span class="close">&times;</span>
  <img class="lightbox-content" id="lightbox-img">
`;
document.body.appendChild(lightbox);

const lightboxImg = document.getElementById("lightbox-img");

document.querySelectorAll('.gallery-img, .popup-image').forEach(img => {
  img.addEventListener('click', () => {
    lightboxImg.src = img.src;
    lightbox.style.display = "block";
    lightbox.classList.add("fade-in");

    setTimeout(() => {
      lightbox.classList.remove("fade-in");
    }, 400);
  });
});

document.querySelector('.close').addEventListener('click', () => {
  lightbox.style.display = "none";
});

lightbox.addEventListener('click', (e) => {
  if (e.target === lightbox) {
    lightbox.style.display = "none";
  }
});