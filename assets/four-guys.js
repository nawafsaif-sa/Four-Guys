/* Progressive enhancement only: forms keep their existing PHP submission paths. */
(() => {
  'use strict';
  const menuButton = document.querySelector('.fg-menu-toggle');
  const navigation = document.querySelector('.fg-navigation');
  menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    navigation?.classList.toggle('is-open', open);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menuButton?.getAttribute('aria-expanded') === 'true') {
      menuButton.click(); menuButton.focus();
    }
  });
  const current = location.pathname.toLowerCase();
  document.querySelectorAll('.fg-navigation a, .fg-sidebar nav a').forEach(link => {
    if (new URL(link.href).pathname.toLowerCase() === current) link.setAttribute('aria-current', 'page');
  });
  const search = document.getElementById('fg-menu-search');
  search?.addEventListener('input', () => {
    const query = search.value.trim().toLocaleLowerCase();
    let total = 0;
    document.querySelectorAll('.fg-menu-section').forEach(section => {
      let visible = 0;
      section.querySelectorAll('.fg-food-card').forEach(card => {
        const match = !query || card.textContent.toLocaleLowerCase().includes(query);
        card.hidden = !match; if (match) visible++;
      });
      section.hidden = visible === 0; total += visible;
    });
    document.querySelector('.fg-search-empty').hidden = total > 0;
  });
  document.querySelectorAll('.fg-categories a').forEach(link => {
    link.addEventListener('click', () => {
      if (search?.value) { search.value = ''; search.dispatchEvent(new Event('input')); }
      document.querySelectorAll('.fg-categories a').forEach(item => item.classList.remove('active'));
      link.classList.add('active');
    });
  });
  // Provide persistent labels rather than relying on placeholder text alone.
  document.querySelectorAll('.fg-auth-panel input[placeholder], .fg-page-contact .field input[placeholder], .fg-page-contact textarea[placeholder]').forEach((field, index) => {
    if (field.labels?.length || field.type === 'hidden') return;
    if (!field.id) field.id = `fg-field-${index}`;
    const label = document.createElement('label');
    label.className = 'fg-field-label'; label.htmlFor = field.id; label.textContent = field.placeholder;
    field.before(label);
    if (field.type === 'password') field.autocomplete = field.name === 'new_password' ? 'new-password' : 'current-password';
    if (field.type === 'tel') field.autocomplete = 'tel';
  });
  document.querySelectorAll('.fg-staff table').forEach(table => {
    if (table.parentElement.classList.contains('fg-table-scroll')) return;
    const scroller = document.createElement('div');
    scroller.className = 'fg-table-scroll'; scroller.tabIndex = 0;
    scroller.setAttribute('role', 'region'); scroller.setAttribute('aria-label', 'Scrollable data table');
    table.before(scroller); scroller.append(table);
  });
})();
