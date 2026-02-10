import './bootstrap.js';
import './styles/app.css';

/**
 * E-Sportify - Main JavaScript File
 * Modern Symfony UX Integration
 */

// Global helper for radio highlights (could be a controller but this is small)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const group = document.querySelectorAll(`input[name="${this.name}"]`);
            group.forEach(r => {
                if (r.parentElement) {
                    r.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    r.parentElement.style.background = 'transparent';
                }
            });
            if (this.parentElement) {
                this.parentElement.style.borderColor = 'var(--primary-blue)';
                this.parentElement.style.background = 'rgba(0, 217, 255, 0.1)';
            }
        });
    });

    const sidebarToggles = document.querySelectorAll('.sidebar-accordion-toggle');
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const group = toggle.closest('.sidebar-group');
            if (!group) {
                return;
            }

            const isOpen = group.classList.contains('open');
            document.querySelectorAll('.sidebar-group.open').forEach(openGroup => {
                if (openGroup === group) {
                    return;
                }

                openGroup.classList.remove('open');
                const openToggle = openGroup.querySelector('.sidebar-accordion-toggle');
                if (openToggle) {
                    openToggle.setAttribute('aria-expanded', 'false');
                }
            });

            group.classList.toggle('open', !isOpen);
            toggle.setAttribute('aria-expanded', (!isOpen).toString());
        });
    });

    document.querySelectorAll('[data-upload]').forEach(box => {
        const input = box.querySelector('[data-upload-input]');
        const preview = box.querySelector('[data-preview]');
        const placeholder = box.querySelector('[data-placeholder]');

        if (!input || !preview || !placeholder) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                preview.style.display = 'none';
                preview.innerHTML = '';
                placeholder.style.display = 'block';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                const mime = file.type || '';
                if (mime.startsWith('video/')) {
                    preview.innerHTML = `<video controls src="${event.target.result}"></video>`;
                } else {
                    preview.innerHTML = `<img src="${event.target.result}" alt="Aperçu">`;
                }
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    });
});

// --------------------------------------------------
// Cart quantity controls (+ / -)
// --------------------------------------------------
document.querySelectorAll('[data-qty-form]').forEach((form) => {
  const input = form.querySelector('[data-qty-input]');
  const minus = form.querySelector('[data-qty-minus]');
  const plus = form.querySelector('[data-qty-plus]');

  if (!input) {
    return;
  }

  const getMin = () => parseInt(input.min || '1', 10);
  const getMax = () => parseInt(input.max || '999', 10);

  const clamp = (value) => {
    const min = getMin();
    const max = getMax();
    const num = Number.isNaN(value) ? min : value;
    return Math.min(Math.max(num, min), max);
  };

  const submit = () => {
    form.submit();
  };

  if (minus) {
    minus.addEventListener('click', () => {
      const next = clamp(parseInt(input.value || '1', 10) - 1);
      input.value = next;
      submit();
    });
  }

  if (plus) {
    plus.addEventListener('click', () => {
      const next = clamp(parseInt(input.value || '1', 10) + 1);
      input.value = next;
      submit();
    });
  }

  input.addEventListener('change', () => {
    input.value = clamp(parseInt(input.value || '1', 10));
    submit();
  });
});
