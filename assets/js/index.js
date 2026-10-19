document.addEventListener('DOMContentLoaded', () => {
  const hero = document.querySelector('.hero');
  const icons = document.querySelectorAll('.hero-icon');
  if (!hero || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  hero.addEventListener('mousemove', (event) => {
    const bounds = hero.getBoundingClientRect();
    const x = (event.clientX - bounds.left) / bounds.width - .5;
    const y = (event.clientY - bounds.top) / bounds.height - .5;
    icons.forEach((icon, index) => {
      const depth = 8 + (index % 3) * 3;
      icon.style.setProperty('--x', `${x * depth}px`);
      icon.style.setProperty('--y', `${y * depth}px`);
    });
  });

  hero.addEventListener('mouseleave', () => icons.forEach((icon) => {
    icon.style.setProperty('--x', '0px');
    icon.style.setProperty('--y', '0px');
  }));
});
