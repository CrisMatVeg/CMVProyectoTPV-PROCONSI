/**
 * Utils.js
 * Utility functions and UI helpers.
 */

/**
 * Show a toast notification
 * @param {string} msg - The message to display (supports HTML)
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
    return n.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
}

/**
 * Format ticket/invoice number
 * @param {number|string} numero 
 * @param {string} fecha 
 * @param {boolean} esFactura 
 */
export function formatTicketNumber(numero, fecha, esFactura) {
    const prefix = esFactura ? 'F' : 'T';
    const d = new Date(fecha.replace(" ", "T"));
    const datePart = `${d.getDate()}${d.getMonth() + 1}${d.getFullYear()}`;
    return `${prefix}-${datePart}-${numero}`;
}

// Map old names to new names for compatibility during transition
export const fmt2 = formatCurrency;

// Export as a single object for compatibility with legacy imports
export const Utils = {
    showToast,
    formatCurrency,
    formatTicketNumber,
    fmt2: formatCurrency
};

// Expose to window for legacy scripts (main.js)
window.formatTicketNumber = formatTicketNumber;
