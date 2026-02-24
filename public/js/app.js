// ────────────────────────────────────────────────
// Thème clair / sombre + persistance
// ────────────────────────────────────────────────
const themeToggle = document.getElementById('theme-toggle');
const themeIcon   = document.getElementById('theme-icon');
const htmlElement = document.documentElement;

function setTheme(theme) {
    htmlElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);

    if (themeIcon) {
        themeIcon.innerHTML = theme === 'dark'
            ? '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>'
            : '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
    }
}

if (themeToggle) {
    // Chargement initial
    const savedTheme = localStorage.getItem('theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const current = htmlElement.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
    });
}

// --------------------------------------------------
// Sidebar toggle (bouton = ouvrir/fermer)
// --------------------------------------------------
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');

if (sidebar && sidebarToggle) {
    const savedState = localStorage.getItem('sidebar');
    if (savedState === 'expanded') {
        sidebar.classList.add('expanded');
    }

    if (!sidebarToggle.dataset.sidebarBound) {
        sidebarToggle.dataset.sidebarBound = '1';
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('expanded');
            localStorage.setItem('sidebar', sidebar.classList.contains('expanded') ? 'expanded' : 'collapsed');
        });
    }
}

// --------------------------------------------------
// Sidebar accordion navigation
// --------------------------------------------------
document.querySelectorAll('.sidebar-accordion-toggle').forEach((toggle) => {
    if (toggle.dataset.accordionBound) {
        return;
    }

    toggle.dataset.accordionBound = '1';
    toggle.addEventListener('click', () => {
        const group = toggle.closest('.sidebar-group');
        if (!group) {
            return;
        }

        const isOpen = group.classList.contains('open');
        document.querySelectorAll('.sidebar-group.open').forEach((openGroup) => {
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

// ────────────────────────────────────────────────
// Animation curseur sidebar (hover = expand)
// ────────────────────────────────────────────────

// --------------------------------------------------
// Admin composer (posts/annonces)
// --------------------------------------------------
function setupAdminComposer(form) {
    const contentInput = form.querySelector('[data-content]');
    const mediaTypeInput = form.querySelector('[data-media-type]');
    const mediaFilenameInput = form.querySelector('[data-media-filename]');
    const linkInput = form.querySelector('[data-link]');
    const mediaFileInput = form.querySelector('[data-media-file]');
    const mediaPreview = form.querySelector('[data-media-preview]');
    const buttons = form.querySelectorAll('[data-media-button]');

    if (!contentInput || !mediaTypeInput || !mediaFilenameInput) {
        return;
    }

    const urlRegex = /(https?:\/\/[^\s]+)/i;

    const setMediaType = (type) => {
        mediaTypeInput.value = type;
    };

    const clearMedia = () => {
        if (mediaTypeInput) {
            mediaTypeInput.value = '';
        }
        if (mediaFilenameInput) {
            mediaFilenameInput.value = '';
        }
        if (mediaFileInput) {
            mediaFileInput.value = '';
        }
        if (mediaPreview) {
            mediaPreview.style.display = 'none';
            mediaPreview.innerHTML = '';
        }
    };

    const setPreview = (file) => {
        if (!mediaPreview) {
            return;
        }

        if (!file) {
            mediaPreview.style.display = 'none';
            mediaPreview.innerHTML = '';
            return;
        }

        const mime = file.type || '';
        const url = URL.createObjectURL(file);

        if (mime.startsWith('video/')) {
            mediaPreview.innerHTML = `<video controls src="${url}"></video>`;
        } else if (mime.startsWith('image/')) {
            mediaPreview.innerHTML = `<img src="${url}" alt="aperçu">`;
        } else {
            mediaPreview.innerHTML = '';
        }

        if (mediaPreview.innerHTML) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'media-remove-btn';
            removeBtn.textContent = 'Retirer';
            removeBtn.addEventListener('click', clearMedia);
            mediaPreview.appendChild(removeBtn);
        }

        mediaPreview.style.display = 'block';
    };

    const detectLink = () => {
        if (mediaFileInput && mediaFileInput.files && mediaFileInput.files.length > 0) {
            return;
        }
        const match = contentInput.value.match(urlRegex);
        if (match) {
            setMediaType('link');
            mediaFilenameInput.value = match[1];
            if (linkInput) {
                linkInput.value = match[1];
            }
        }
    };

    contentInput.addEventListener('input', detectLink);
    detectLink();

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const type = btn.getAttribute('data-media-button');
            if (type === 'link') {
                setMediaType('link');
                detectLink();
                contentInput.focus();
                return;
            }

            setMediaType(type);
            if (mediaFileInput) {
                mediaFileInput.click();
            }
        });
    });

    if (mediaFileInput) {
        mediaFileInput.addEventListener('change', () => {
            if (mediaFileInput.files && mediaFileInput.files.length > 0) {
                const file = mediaFileInput.files[0];
                const mime = file.type || '';
                setMediaType(mime.startsWith('video/') ? 'video' : 'image');
                mediaFilenameInput.value = '';
                setPreview(file);
            } else {
                setPreview(null);
            }
        });
    }

    if (mediaPreview && mediaPreview.innerHTML.trim() !== '') {
        mediaPreview.style.display = 'block';
        const existingRemove = mediaPreview.querySelector('.media-remove-btn');
        if (existingRemove) {
            existingRemove.addEventListener('click', clearMedia);
        }
    }
}

window.setupAdminComposer = setupAdminComposer;
document.querySelectorAll('form[data-composer]').forEach(setupAdminComposer);
document.querySelectorAll('[data-composer-front]').forEach(setupAdminComposer);

// --------------------------------------------------
// Admin upload preview (drag & drop zone)
// --------------------------------------------------
document.querySelectorAll('[data-upload]').forEach((box) => {
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
