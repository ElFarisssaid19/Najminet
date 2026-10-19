document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.ripple-button').forEach((button) => button.addEventListener('click', (event) => {
    const ripple = document.createElement('span');
    const size = Math.max(button.clientWidth, button.clientHeight);
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - button.getBoundingClientRect().left - size / 2}px`;
    ripple.style.top = `${event.clientY - button.getBoundingClientRect().top - size / 2}px`;
    button.querySelector('.ripple')?.remove();
    button.append(ripple);
  }));
});
