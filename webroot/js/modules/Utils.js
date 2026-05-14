/**
 * Utils.js
 * Utility functions and UI helpers.
 */

/**
 * Escapa caracteres HTML para prevenir XSS al insertar texto de usuario en innerHTML.
 * @param {string} str
 * @returns {string}
 */
export function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Show a toast notification.
 * msg puede contener HTML de confianza (iconos, negritas).
 * Para datos de usuario usar escapeHtml() antes de concatenar.
 * @param {string} msg
 * @param {string} type - 'success', 'error', 'info', etc.
 */
export function showToast(msg, type = "info") {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = msg;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "toastOut 0.5s ease forwards";
    setTimeout(() => toast.remove(), 500);
  }, 4000);
}

/**
 * Format currency (Standard formatter)
 * @param {number|string} val 
 */
export function formatCurrency(val) {
    const n = parseFloat(val);
    if (isNaN(n)) return "0,00€";
    
    // Solo mostrar más de 2 decimales si ALGÚN producto en el carrito tiene más de 2 decimales
    let requiereAltaPrecision = false;
    if (typeof window !== 'undefined' && window.cart) {
        requiereAltaPrecision = Object.values(window.cart).some(item => {
            const precio = parseFloat(item.price || 0);
            return (Math.round(precio * 100) / 100) !== precio;
        });
    }

    const str = n.toString();
    const parts = str.split('.');
    
    if (requiereAltaPrecision && parts.length > 1 && parts[1].length > 2) {
        // Formato de alta precisión (solo si el producto lo requiere)
        return n.toString().replace(".", ",") + " €";
    }
    
    return n.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
}

/**
 * Format ticket/invoice number
 * @param {number|string} numero 
 * @param {string} fecha 
 * @param {boolean} esFactura 
 */
export function formatTicketNumber(numero, fecha, esFactura, tipoDocumento) {
    let prefix;
    if (tipoDocumento === 'abono') {
        prefix = 'A';
    } else {
        prefix = esFactura ? 'F' : 'T';
    }
    const d = new Date(fecha.replace(" ", "T"));
    const datePart = `${d.getDate()}${d.getMonth() + 1}${d.getFullYear()}`;
    return `${prefix}-${datePart}-${numero}`;
}

// Map old names to new names for compatibility during transition
export const fmt2 = formatCurrency;

// Export as a single object for compatibility with legacy imports
export const Utils = {
    showToast,
    escapeHtml,
    formatCurrency,
    formatTicketNumber,
    fmt2: formatCurrency
};

// Expose to window for legacy scripts (main.js)
window.formatTicketNumber = formatTicketNumber;

/**
 * FocusTrap: Manages keyboard accessibility for modal dialogs.
 * Traps focus inside the modal, restores it on close, and closes on Escape.
 *
 * Usage:
 *   const trap = FocusTrap.activate(modalElement, onCloseCallback);
 *   FocusTrap.deactivate(trap);
 */
export const FocusTrap = {
  _active: null,

  /**
   * Activate a focus trap on a modal element.
   * @param {HTMLElement} modalEl - The wrapper element (role="dialog")
   * @param {Function}    onClose - Callback to run when Escape is pressed
   * @returns {Object} trap handle (pass to deactivate)
   */
  activate(modalEl, onClose) {
    if (!modalEl) return null;

    // Save the element that opened the modal so we can restore focus on close
    const previousFocus = document.activeElement;

    // All focusable elements inside the modal
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const getFocusable = () => Array.from(modalEl.querySelectorAll(FOCUSABLE)).filter(el => !el.closest('[hidden]') && getComputedStyle(el).display !== 'none');

    // Move initial focus to the first focusable element (or the modal itself)
    const focusables = getFocusable();
    if (focusables.length) {
      focusables[0].focus();
    } else {
      modalEl.setAttribute('tabindex', '-1');
      modalEl.focus();
    }

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        if (typeof onClose === 'function') onClose();
        return;
      }
      if (e.key !== 'Tab') return;

      const focusable = getFocusable();
      if (!focusable.length) { e.preventDefault(); return; }

      const first = focusable[0];
      const last  = focusable[focusable.length - 1];

      if (e.shiftKey) {
        // Shift+Tab: wrap from first → last
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        // Tab: wrap from last → first
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    modalEl.addEventListener('keydown', handleKeyDown);

    const trap = { modalEl, handleKeyDown, previousFocus };
    this._active = trap;
    return trap;
  },

  /**
   * Deactivate a focus trap and restore the previously focused element.
   * @param {Object} trap - The handle returned by activate()
   */
  deactivate(trap) {
    if (!trap) return;
    trap.modalEl.removeEventListener('keydown', trap.handleKeyDown);
    if (trap.previousFocus && typeof trap.previousFocus.focus === 'function') {
      trap.previousFocus.focus();
    }
    if (this._active === trap) this._active = null;
  }
};

// Expose to window for legacy scripts
window.FocusTrap = FocusTrap;
