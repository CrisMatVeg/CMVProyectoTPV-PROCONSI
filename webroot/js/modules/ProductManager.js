/**
 * ProductManager.js
 * Handles product management, editing, and stock operations.
 */
import { AppState, PRODUCTS } from './AppConfig.js';
import { ApiService } from './ApiService.js';
import { Utils } from './Utils.js';

export const ProductManager = {
    /**
     * Opens the modal for editing a product.
     * @param {number} id - Product ID.
     */
    async editProduct(id) {
        const p = PRODUCTS.find((x) => x.id === id);
        if (!p) return;

        document.getElementById("editId").value = p.id;
        document.getElementById("editName").value = p.name;
        document.getElementById("editSku").value = p.codigo;
        const checkPrecision = document.getElementById("editMantenerPrecision");
        if (checkPrecision) {
            const precisionActiva = !!(parseInt(p.mantener_precision || 0));
            checkPrecision.checked = precisionActiva;
            
            // Si la precisión está desactivada, forzamos el formato de 2 decimales
            if (!precisionActiva) {
                const pVenta = parseFloat(p.price) || 0;
                document.getElementById("editPrice").value = pVenta.toFixed(2);
            } else {
                document.getElementById("editPrice").value = p.price;
            }
        }
        const editIvaEl = document.getElementById("editIva");
        if (editIvaEl) editIvaEl.value = p.iva || 21;
        document.getElementById("editMesesGarantia").value = p.meses_garantia || 24;
        document.getElementById("editEmoji").value = p.icono;
        document.getElementById("editAtributos").value = p.atributos || '';

        const preview = document.getElementById("editImgPreview");
        if (p.icono && p.icono.startsWith("data:image")) {
            preview.innerHTML = `<img src="${p.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
        } else {
            preview.innerHTML = `<span style="font-size: 24px;">${p.icono || "📦"}</span>`;
        }

        document.getElementById("editModal").classList.add("visible");
    },

    /**
     * Saves the edited product data.
     */
    async saveEdit() {
        const id = parseInt(document.getElementById("editId")?.value);
        const name = document.getElementById("editName")?.value.trim();
        const codigo = document.getElementById("editSku")?.value.trim();
        const price = parseFloat(document.getElementById("editPrice")?.value.replace(',', '.')) || 0;
        const iva = parseFloat(document.getElementById("editIva")?.value.replace(',', '.')) || 21;
        const mesesGarantia = parseInt(document.getElementById("editMesesGarantia")?.value) || 24;
        const icono = document.getElementById("editEmoji")?.value.trim();
        const atributos = document.getElementById("editAtributos")?.value || null;
        const mantenerPrecision = document.getElementById("editMantenerPrecision")?.checked ? 1 : 0;

        try {
            const data = await ApiService.post('gestionProducto.php', {
                accion: "editar",
                id,
                nombre: name,
                referencia: codigo,
                precio_venta: price,
                iva,
                meses_garantia: mesesGarantia,
                icono,
                atributos,
                mantener_precision: mantenerPrecision
            });

            if (data.ok) {
                const p = PRODUCTS.find((x) => x.id === id);
                Object.assign(p, { name, codigo, price, iva, meses_garantia: mesesGarantia, icono, atributos, mantener_precision: mantenerPrecision });
                document.getElementById("editModal").classList.remove("visible");
                Utils.showToast("Producto actualizado", "success");
                // Trigger global refresh if needed
            } else {
                throw new Error(data.error);
            }
        } catch (err) {
            Utils.showToast(err.message || "Error al guardar", "error");
        }
    },
    /**
     * Toggles the active/inactive status of a product.
     * @param {number} id - Product ID.
     */
    async toggleBaja(id) {
        try {
            const data = await ApiService.post('gestionProducto.php', { accion: "baja", id });
            if (!data.ok) throw new Error(data.error);

            const p = PRODUCTS.find((x) => x.id === id);
            if (p) {
                p.inactive = !data.activo;
                // If inactivated, remove from cart
                if (p.inactive && AppState.cart[id]) {
                    const newCart = { ...AppState.cart };
                    delete newCart[id];
                    AppState.cart = newCart;
                }
                Utils.showToast(p.inactive ? "Producto dado de baja" : "Producto reactivado", "success");
            }
        } catch (err) {
            Utils.showToast(err.message || "Error al cambiar estado", "error");
        }
    },

    /**
     * Prepares removal of a product. 
     * Note: Confirmation is handled via TpvApp to manage the modal state.
     * @param {number} id - Product ID.
     */
    async deleteProduct(id) {
        const p = PRODUCTS.find((x) => x.id === id);
        if (!p) return;

        const elName = document.getElementById("delName");
        if (elName) elName.textContent = p.name;

        const confirmBtn = document.getElementById("delConfirmBtn");
        if (confirmBtn) {
            confirmBtn.onclick = async () => {
                try {
                    const data = await ApiService.post('gestionProducto.php', { accion: "eliminar", id });
                    if (!data.ok) throw new Error(data.error);

                    const idx = PRODUCTS.findIndex((x) => x.id === id);
                    if (idx !== -1) PRODUCTS.splice(idx, 1);

                    // Remove from cart
                    if (AppState.cart[id]) {
                        const newCart = { ...AppState.cart };
                        delete newCart[id];
                        AppState.cart = newCart;
                    }

                    document.getElementById("deleteModal").classList.remove("visible");
                    Utils.showToast("Producto eliminado", "success");
                    // TpvApp handles the UI refresh via the return value or callback
                } catch (err) {
                    Utils.showToast(err.message || "Error al eliminar", "error");
                }
            };
        }
        document.getElementById("deleteModal").classList.add("visible");
    },

    /**
     * Fetch products from the server with filters and pagination.
     * @param {boolean} reset - If true, resets offset and clears current products.
     */
    async loadProducts(reset = false) {
        if (AppState.isLoading) return;
        if (!reset && !AppState.canLoadMore) return;

        AppState.isLoading = true;
        const grid = document.getElementById("productsGrid");
        let paginationSpinner = null;
        
        // Update UI state for loading
        if (grid) {
            if (reset) {
                AppState.offset = 0;
                AppState.canLoadMore = true;
                grid.innerHTML = `<div class="w-100 text-center p-40 opacity-50"><i class="fa-solid fa-circle-notch fa-spin fs-24 mb-12"></i><br>Cargando catálogo...</div>`;
            } else {
                // Add pagination spinner at the bottom
                paginationSpinner = document.createElement("div");
                paginationSpinner.className = "w-100 text-center p-20 opacity-50 product-load-more-spinner";
                paginationSpinner.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin fs-16 mb-4"></i><br><span class="fs-11">Cargando más productos...</span>`;
                grid.appendChild(paginationSpinner);
            }
        }

        try {
            const data = await ApiService.post('gestionProducto.php?accion=listar', {
                limit: AppState.limit,
                offset: AppState.offset,
                term: AppState.searchTerm,
                cat: AppState.activeCat
            });

            if (data.ok) {
                const newProducts = (data.productos || []).map(p => ({
                    id: p.id,
                    name: p.nombre,
                    codigo: p.referencia,
                    price: p.precio_venta, // Keeping as string/value from server
                    icono: p.icono || '📦',
                    cat: p.categoria,
                    stock: parseInt(p.stock),
                    inactive: !p.activo,
                    es_pack: p.es_pack === 1,
                    atributos: p.atributos,
                    mantener_precision: p.mantener_precision,
                    componentes_pack: p.componentes_pack
                }));

                if (reset) {
                    PRODUCTS.length = 0;
                    PRODUCTS.push(...newProducts);
                } else {
                    PRODUCTS.push(...newProducts);
                }

                AppState.canLoadMore = newProducts.length >= AppState.limit;
                AppState.offset += newProducts.length;
            } else {
                throw new Error(data.error);
            }
        } catch (err) {
            console.error("ProductManager: Error loading products:", err);
            Utils.showToast("Error al cargar productos", "error");
            
            if (reset && grid) {
                grid.innerHTML = `<div class="w-100 text-center p-40 text-red opacity-80">
                    <i class="fa-solid fa-circle-exclamation fs-24 mb-12"></i><br>
                    Error al cargar el catálogo.<br>
                    <button onclick="app.refreshUI()" class="btn-primary mt-16" style="height:36px; padding:0 20px;">Reintentar</button>
                </div>`;
            }
        } finally {
            AppState.isLoading = false;
            // Remove pagination spinner if it exists
            if (paginationSpinner && paginationSpinner.parentNode) {
                paginationSpinner.remove();
            }
        }
    }
};
