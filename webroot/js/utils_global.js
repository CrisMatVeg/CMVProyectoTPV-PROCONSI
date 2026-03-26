/**
 * Global utilities for ElectroBazar TPV
 * Loaded as a regular script (not module) to ensure availability
 */

window.formatTicketNumber = function(numero, fecha, esFactura) {
    const prefix = esFactura ? 'F' : 'T';
    let date;
    
    if (!fecha) {
        date = new Date();
    } else if (typeof fecha === 'string') {
        // Handle format "YYYY-MM-DD HH:mm:ss"
        const f = fecha.includes('T') ? fecha : fecha.replace(' ', 'T');
        date = new Date(f);
    } else {
        date = new Date(fecha);
    }

    // Identical logic to PHP version in VentaPDO.php
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const datePart = `${day}${month}${year}`;
    
    return `${prefix}-${datePart}-${numero}`;
};
