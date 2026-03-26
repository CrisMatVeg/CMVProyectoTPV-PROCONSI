function validarDNI_NIE(value) {
    const validChars = 'TRWAGMYFPDXBNJZSQVHLCKE';
    const nifRexp = /^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKET]$/i;
    const nieRexp = /^[XYZ][0-9]{7}[TRWAGMYFPDXBNJZSQVHLCKET]$/i;
    const str = value.toString().toUpperCase().trim();

    if (!nifRexp.test(str) && !nieRexp.test(str)) return false;

    const nie = str
        .replace(/^[X]/, '0')
        .replace(/^[Y]/, '1')
        .replace(/^[Z]/, '2');

    const letter = str.substr(-1);
    const charIndex = parseInt(nie.substr(0, 8)) % 23;

    return validChars.charAt(charIndex) === letter;
}

function validarCIF(cif) {
    if (!cif || cif.trim().length !== 9) return false;
    cif = cif.trim().toUpperCase();

    const letters = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
    const digits = cif.substr(1, cif.length - 2);
    const control = cif.substr(cif.length - 1);
    
    if (!cif.match(/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[A-Z0-9]$/i)) return false;

    let sum = 0;
    for (let i = 0; i < digits.length; i++) {
        let digit = parseInt(digits[i]);
        if (i % 2 === 0) {
            digit *= 2;
            if (digit > 9) digit = parseInt(digit / 10) + (digit % 10);
        }
        sum += digit;
    }

    let dec = Math.ceil(sum / 10) * 10;
    let res = dec - sum;
    let controlDigit = res === 10 ? 0 : res;
    let controlLetter = letters[controlDigit];

    return (parseInt(control) === controlDigit || control === controlLetter);
}

function validarDocumento(doc) {
    if (!doc || doc.trim() === '') return true; // Let the 'required' attribute handle emptiness if needed
    const value = doc.trim().toUpperCase();
    return validarDNI_NIE(value) || validarCIF(value);
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
