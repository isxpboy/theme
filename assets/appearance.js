(() => {
  'use strict';
  const key = 'lei-notes-appearance';
  const allowed = ['auto', 'light', 'dark'];
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  let mode = ['auto','light','dark'].includes(window.LeiThemeSettings?.appearance) ? window.LeiThemeSettings.appearance : 'auto';
  try { const saved = localStorage.getItem(key); if (allowed.includes(saved)) mode = saved; } catch { /* System mode works without storage. */ }
  function apply() {
    const theme = mode === 'auto' ? (media.matches ? 'dark' : 'light') : mode;
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.appearance = mode;
    document.documentElement.style.colorScheme = theme;
    document.dispatchEvent(new CustomEvent('lei:appearance-change', { detail: { mode, theme } }));
  }
  window.leiAppearance = {
    get mode() { return mode; },
    set(next) {
      if (!allowed.includes(next)) return;
      mode = next;
      try { localStorage.setItem(key, mode); } catch { /* Storage is optional. */ }
      apply();
    }
  };
  media.addEventListener('change', () => { if (mode === 'auto') apply(); });
  window.addEventListener('storage', (event) => {
    if (event.key === key || event.key === null) { mode = allowed.includes(event.newValue) ? event.newValue : 'auto'; apply(); }
  });
  apply();
})();
