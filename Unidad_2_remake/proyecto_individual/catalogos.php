<?php
// Datos y validaciones compartidos por el formulario y por acciones.php.
const PAISES = ['México', 'Estados Unidos', 'Canadá', 'Guatemala', 'Colombia', 'Argentina', 'España', 'Otro'];

function fecha_valida($f) {
    $d = DateTime::createFromFormat('Y-m-d', (string) $f);
    return $d && $d->format('Y-m-d') === $f;
}

// 18 caracteres: 4 letras, fecha AAMMDD, sexo, entidad, 3 consonantes, homoclave y digito
function curp_valida($c) {
    return (bool) preg_match('/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HMX]'
        . '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)'
        . '[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/', $c);
}
