document.addEventListener('DOMContentLoaded', () => {
  const menuIcons = document.querySelectorAll('.menu-icon');

  menuIcons.forEach(icon => {
    icon.addEventListener('click', e => {
      e.stopPropagation(); // prevent document click from hiding immediately
      const dropdown = icon.nextElementSibling;

      // hide all other dropdowns
      document.querySelectorAll('.dropdown-menu').forEach(dm => {
        if (dm !== dropdown) dm.style.display = 'none';
      });

      // toggle current dropdown
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
  });

  // hide dropdown when clicking outside
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(dm => dm.style.display = 'none');
  });
});
