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
});
