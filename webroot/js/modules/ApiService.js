/**
 * ApiService.js
 * Handles all network requests to the backend.
 */

export const ApiService = {
  /**
   * Base request wrapper
   */
  async request(url, options = {}) {
    try {
      const response = await fetch(url, {
        headers: {
          "Content-Type": "application/json",
          ...options.headers,
        },
        ...options,
      });

      if (!response.ok) {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
          const errorData = await response.json();
          throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      // Check if it's JSON or text
      const contentType = response.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        const data = await response.json();
        if (data.ok === false) {
          throw new Error(data.error || "Error desconocido en el servidor");
        }
        return data;
      }
      
      return await response.text();
    } catch (error) {
      console.error(`ApiService [${url}]:`, error);
      throw error;
    }
  },
  
  async get(url) {
    return this.request(url, { method: "GET" });
  },

  async post(url, data) {
    // Determine path
    const path = url.startsWith("http") || url.startsWith("./") ? url : `./api/${url}`;
    return this.request(path, {
      method: "POST",
      body: JSON.stringify(data),
    });
  },

  // Ticket & Sales
  async sendTicketEmail(numTicket, destinatario, tipo = "ticket") {
    return this.request("./api/enviarVentaEmail.php", {
      method: "POST",
      body: JSON.stringify({ numTicket, destinatario, tipo }),
    });
  },

  async processRefund(payload) {
    return this.request("api/gestionDevolucion.php", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },

  async liquidarVenta(idVenta, importe, metodo) {
    return this.request("./api/liquidarVenta.php", {
      method: "POST",
      body: JSON.stringify({ idVenta, importe, metodo }),
    });
  },

  // Products
  async manageProduct(action, productData) {
    return this.request("./api/gestionProducto.php", {
      method: "POST",
      body: JSON.stringify({ accion: action, ...productData }),
    });
  },
  
  async getComisiones(idEntidad) {
    return this.request(`./api/getComisiones.php?id=${idEntidad}`);
  },

  // Logging
  async logUI(nivel, mensaje, endpoint = null, data = null) {
    try {
      return await this.request("api/registrarLogUI.php", {
        method: "POST",
        body: JSON.stringify({ nivel, mensaje, endpoint, data }),
      });
    } catch (e) {
    }
  },

  async getCajaEstadoActual() {
    return this.request("./api/cajaEstadoActual.php");
  }
};
