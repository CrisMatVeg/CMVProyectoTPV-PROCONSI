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
        document.getElementById("editPrice").value = p.price;
        document.getElementById("editIva").value = p.iva || 21;
        document.getElementById("editMesesGarantia").value = p.meses_garantia || 24;
        document.getElementById("editEmoji").value = p.icono;

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
        const price = parseFloat(document.getElementById("editPrice")?.value);
        const iva = parseFloat(document.getElementById("editIva")?.value) || 21;
        const mesesGarantia = parseInt(document.getElementById("editMesesGarantia")?.value) || 24;
        const icono = document.getElementById("editEmoji")?.value.trim();

        try {
            const data = await ApiService.post('gestionProducto.php', {
                accion: "editar",
                id,
                nombre: name,
                referencia: codigo,
                precio_venta: price,
                iva,
                meses_garantia: mesesGarantia,
                icono
            });

            if (data.ok) {
                const p = PRODUCTS.find((x) => x.id === id);
                Object.assign(p, { name, codigo, price, iva, meses_garantia: mesesGarantia, icono });
                document.getElementById("editModal").classList.remove("visible");
                Utils.showToast("Producto actualizado", "success");
                // Trigger global refresh if needed
            } else {
                throw new Error(data.error);
            }
        } catch (err) {
            Utils.showToast("Error al guardar", "error");
        }
    }
};
