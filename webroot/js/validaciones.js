function validarDNI_NIE(originalValue) {
    const validChars = 'TRWAGMYFPDXBNJZSQVHLCKE';
    let str = originalValue.toString().toUpperCase().replace(/[\s-]/g, '');

    // NIE: X, Y, Z
    // NIF especiales: K, L, M (se validan igual que el NIF pero el primero es una letra)
    const nifRexp = /^[0-9]{8}[A-Z]$/i;
    const nieRexp = /^[XYZ][0-9]{7}[A-Z]$/i;
    const specialNifRexp = /^[KLM][0-9]{7}[A-Z]$/i;

    if (!nifRexp.test(str) && !nieRexp.test(str) && !specialNifRexp.test(str)) return false;

    // Convertir NIE/Especiales a formato numérico para el cálculo
    let numeric = str;
    if (nieRexp.test(str)) {
        numeric = str.replace(/^[X]/, '0').replace(/^[Y]/, '1').replace(/^[Z]/, '2');
    } else if (specialNifRexp.test(str)) {
        // K, L, M suelen ser equivalentes a quitar la letra y tomar los números
        numeric = str.substring(1);
    }
    
    // Si tenemos 8 caracteres numéricos al final
    const numbersPart = numeric.substring(0, numeric.length - 1);
    const letter = str.substr(-1);
    const charIndex = parseInt(numbersPart) % 23;

    return validChars.charAt(charIndex) === letter;
}

function validarCIF(cif) {
    cif = cif.toString().toUpperCase().replace(/[\s-]/g, '');
    if (cif.length !== 9) return false;

    const letters = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
    const type = cif.charAt(0);
    const digitsIn = cif.substring(1, 8);
    const control = cif.charAt(8);
    
    if (!cif.match(/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[A-Z0-9]$/i)) return false;

    let sum = 0;
    for (let i = 0; i < digitsIn.length; i++) {
        let digit = parseInt(digitsIn[i]);
        if (i % 2 === 0) { // Posiciones impares real (1, 3, 5, 7)
            digit *= 2;
            if (digit > 9) digit = Math.floor(digit / 10) + (digit % 10);
        }
        sum += digit;
    }

    const lastDigitSum = sum % 10;
    const controlResult = lastDigitSum === 0 ? 0 : 10 - lastDigitSum;
    const controlLetter = letters[controlResult];

    // Tipos de control según la letra inicial
    const isOnlyLetter = 'PQRSW'.includes(type);
    const isOnlyNumber = 'ABEH'.includes(type);

    if (isOnlyLetter) {
        return control === controlLetter;
    } else if (isOnlyNumber) {
        return parseInt(control) === controlResult;
    } else {
        // Admite ambos (C, D, F, G, J, N, U, V)
        return (parseInt(control) === controlResult || control === controlLetter);
    }
}

function validarDocumento(doc) {
    if (!doc || doc.trim() === '') return true; 
    const cleaned = doc.trim().toUpperCase().replace(/[\s-]/g, '');
    
    // Si empieza por número, o por X, Y, Z, K, L, M -> Probablemente NIF/NIE
    if (/^[0-9XYZKLM]/.test(cleaned)) {
        return validarDNI_NIE(cleaned);
    }
    // Si empieza por letra de empresa -> CIF
    return validarCIF(cleaned);
}

function validarTelefono(tel) {
    if (!tel || tel.trim() === '') return true; 
    const value = tel.replace(/\s+/g, '');
    // Permissive format: 9 digits starting with 6,7,8,9 (Spanish) OR international format starting with +
    const rx = /^(\+34|0034|34)?[6789]\d{8}$|^(\+[1-9]\d{6,14})$/;
    return rx.test(value);
}

function validarFechas(inicio, fin) {
    if (!inicio || !fin) return true;
    const dInicio = new Date(inicio);
    const dFin = new Date(fin);
    return dFin >= dInicio;
}
