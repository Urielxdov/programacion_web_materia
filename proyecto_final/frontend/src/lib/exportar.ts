/**
 * Genera un archivo CSV en el navegador y lo descarga.
 * Se construye un Blob, se crea un enlace <a> temporal en el DOM, se le da clic
 * de forma programática y se elimina (manipulación directa del DOM).
 */
export function descargarCsv(nombreArchivo: string, encabezados: string[], filas: (string | number | null)[][]) {
  const escapar = (v: string | number | null) => {
    const s = v === null || v === undefined ? '' : String(v);
    return /[",\n;]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const contenido = [encabezados, ...filas].map((f) => f.map(escapar).join(',')).join('\r\n');
  // BOM para que Excel reconozca los acentos (UTF-8).
  const blob = new Blob(['﻿' + contenido], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);

  const enlace = document.createElement('a');
  enlace.href = url;
  enlace.download = nombreArchivo;
  enlace.style.display = 'none';
  document.body.appendChild(enlace);
  enlace.click();
  document.body.removeChild(enlace);
  URL.revokeObjectURL(url);
}
