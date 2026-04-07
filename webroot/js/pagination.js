/**
 * Paginador Universal Dinámico
 * Se aplica a todas las tablas con la clase .data-table
 */
class TablePaginator {
    constructor(table, options = {}) {
        this.table = table;
        this.tbody = table.querySelector('tbody');
        if (!this.tbody) return;

        this.rowsPerPage = options.rowsPerPage || 10;
        this.currentPage = 1;
        this.paginationContainer = null;
        
        // Atributo interno para marcar filas ocultas por el paginador
        this.HIDDEN_ATTR = 'data-page-hidden';

        this.init();
        this.observeFilters();
    }

    init() {
        if (!this.paginationContainer) {
            this.paginationContainer = document.createElement('div');
            this.paginationContainer.className = 'pagination-container';
            // Insertar después de la tabla (o del contenedor con scroll si existe)
            let insertTarget = this.table;
            if (this.table.parentElement.classList.contains('table-responsive')) {
                insertTarget = this.table.parentElement;
            }
            insertTarget.parentNode.insertBefore(this.paginationContainer, insertTarget.nextSibling);
        }
        
        this.update();
    }

    // Retorna las filas dinámicamente ignorando las ocultas del DOM y las cabeceras/empty-states
    getValidRows() {
        return Array.from(this.tbody.querySelectorAll('tr')).filter(row => {
            if (row.classList.contains('empty-state')) return false;
            
            // Verificar si está oculta por otros scripts de búsqueda.
            // Ignoramos nuestro propio estado data-page-hidden simulando que está visible para este check.
            const wasPageHidden = row.hasAttribute(this.HIDDEN_ATTR);
            if (wasPageHidden) row.removeAttribute(this.HIDDEN_ATTR);
            
            // Verificar visibilidad (inline style display none o class hidden/d-none)
            const isHiddenByFilter = row.style.display === 'none' || 
                                     row.classList.contains('hidden') || 
                                     row.classList.contains('d-none');
            
            if (wasPageHidden) row.setAttribute(this.HIDDEN_ATTR, 'true');
            
            return !isHiddenByFilter;
        });
    }

    update() {
        const rows = this.getValidRows();
        const totalRows = rows.length;
        const totalPages = Math.ceil(totalRows / this.rowsPerPage) || 1;

        if (this.currentPage > totalPages) {
            this.currentPage = totalPages;
        }

        // Mostrar / Ocultar filas basado en página actual
        const startIndex = (this.currentPage - 1) * this.rowsPerPage;
        const endIndex = startIndex + this.rowsPerPage;

        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.removeAttribute(this.HIDDEN_ATTR);
                row.style.visibility = 'visible';
            } else {
                row.setAttribute(this.HIDDEN_ATTR, 'true');
            }
        });

        this.renderControls(totalPages);
    }

    renderControls(totalPages) {
        this.paginationContainer.innerHTML = '';
        if (totalPages <= 1) return; // Si solo hay 1 página (o 0), no mostramos controles

        // Botón Anterior
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.onclick = () => this.goToPage(this.currentPage - 1);
        this.paginationContainer.appendChild(prevBtn);

        // Lógica para max botones a mostrar
        const maxButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = startPage + maxButtons - 1;

        if (endPage > totalPages) {
            endPage = totalPages;
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        // Primera página si no está en el rango
        if (startPage > 1) {
            this.paginationContainer.appendChild(this.buildPageBtn(1));
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.innerText = '...';
                this.paginationContainer.appendChild(ellipsis);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            this.paginationContainer.appendChild(this.buildPageBtn(i));
        }

        // Última página si no está en el rango
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.innerText = '...';
                this.paginationContainer.appendChild(ellipsis);
            }
            this.paginationContainer.appendChild(this.buildPageBtn(totalPages));
        }

        // Botón Siguiente
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        nextBtn.disabled = this.currentPage === totalPages;
        nextBtn.onclick = () => this.goToPage(this.currentPage + 1);
        this.paginationContainer.appendChild(nextBtn);
    }

    buildPageBtn(pageNum) {
        const btn = document.createElement('button');
        btn.className = 'pagination-btn';
        if (pageNum === this.currentPage) {
            btn.classList.add('active');
        }
        btn.innerText = pageNum;
        btn.onclick = () => this.goToPage(pageNum);
        return btn;
    }

    goToPage(pageNum) {
        this.currentPage = pageNum;
        this.update();
        // Opcional: hacer algo de scroll si la tabla es grande
        // this.table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Observa si otros scripts modifican las filas (ej. buscadores/filtros)
    observeFilters() {
        const observer = new MutationObserver((mutations) => {
            let shouldUpdate = false;
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    shouldUpdate = true;
                    break;
                } else if (mutation.type === 'attributes') {
                    // Ignoramos mutaciones provocadas por nuestro propio attributo data-page-hidden
                    if (mutation.attributeName === 'style' || mutation.attributeName === 'class') {
                        shouldUpdate = true;
                        break;
                    }
                }
            }
            if (shouldUpdate) {
                // Pequeño debounce para no saturar si hay muchas mutaciones
                clearTimeout(this.updateTimeout);
                this.updateTimeout = setTimeout(() => {
                    // Resetear a la página 1 cuando cambia el filtro si estamos despistados
                    // (Opcional: this.currentPage = 1;)
                    this.update();
                }, 50);
            }
        });

        observer.observe(this.tbody, {
            childList: true,
            attributes: true,
            attributeFilter: ['style', 'class'],
            subtree: true
        });
    }
}

// Inicializador automático para todas las tablas con clase .data-table
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.data-table').forEach(table => {
        // Ignorar tablas muy pequeñas incrustadas o con clase explícita exclude-pagination
        if (!table.classList.contains('exclude-pagination')) {
            new TablePaginator(table, { rowsPerPage: 10 });
        }
    });
});
