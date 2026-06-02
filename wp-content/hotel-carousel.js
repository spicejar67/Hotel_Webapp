var currentSlide = 0;
var slides = document.querySelectorAll('.carousel-slide');
var dots = document.querySelectorAll('.dot');

function showSlide(n) {
  slides.forEach(function(s) { s.classList.remove('active'); });
  dots.forEach(function(d) { d.classList.remove('active'); });
  slides[n].classList.add('active');
  dots[n].classList.add('active');
  currentSlide = n;
}

function changeSlide(dir) {
  var n = currentSlide + dir;
  if (n >= slides.length) n = 0;
  if (n < 0) n = slides.length - 1;
  showSlide(n);
}

function goSlide(n) { showSlide(n); }

setInterval(function() { changeSlide(1); }, 4000);
