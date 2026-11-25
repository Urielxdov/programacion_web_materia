/**
 * Ilustraciones vectoriales propias (SVG) con la paleta de la marca.
 * Son decorativas (aria-hidden): la información siempre está también en texto.
 */

export function Logo({ className = 'size-11' }: { className?: string }) {
  return (
    <svg viewBox="0 0 64 64" className={className} aria-hidden>
      <rect width="64" height="64" rx="16" fill="var(--color-marca-700)" />
      <circle cx="32" cy="32" r="19" fill="none" stroke="var(--color-marca-200)" strokeWidth="4" strokeDasharray="88 12" />
      <path d="M22 39c0-10 7-17 20-18-1 13-8 20-18 20" fill="var(--color-marca-200)" />
      <path d="M22 43c4-7 8-12 15-16" stroke="var(--color-marca-700)" strokeWidth="3" fill="none" strokeLinecap="round" />
    </svg>
  );
}

/** Escena principal: equipos que circulan alrededor de una hoja (economía circular). */
export function IlustracionCircular({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 480 400" className={className} aria-hidden>
      <defs>
        <linearGradient id="fondo-hero" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor="var(--color-marca-100)" />
          <stop offset="1" stopColor="var(--color-tierra-100)" />
        </linearGradient>
      </defs>
      <circle cx="240" cy="200" r="185" fill="url(#fondo-hero)" />
      {/* flechas circulares */}
      <g fill="none" stroke="var(--color-marca-500)" strokeWidth="10" strokeLinecap="round">
        <path d="M120 120a150 150 0 0 1 200-40" />
        <path d="M380 170a150 150 0 0 1-60 180" />
        <path d="M200 345a150 150 0 0 1-110-170" />
      </g>
      <g fill="var(--color-marca-500)">
        <path d="M318 62l26 20-32 8z" />
        <path d="M312 362l-6-32 30 14z" />
        <path d="M80 165l20-26 10 30z" />
      </g>
      {/* hoja central */}
      <path d="M180 250c0-60 40-100 120-105-5 75-45 115-110 115" fill="var(--color-marca-600)" />
      <path d="M185 262c22-40 48-68 90-90" stroke="var(--color-marca-100)" strokeWidth="6" fill="none" strokeLinecap="round" />
      {/* laptop */}
      <g transform="translate(46 210)">
        <rect x="8" y="0" width="92" height="58" rx="6" fill="#fff" stroke="var(--color-tinta)" strokeWidth="5" />
        <rect x="18" y="10" width="72" height="38" rx="2" fill="var(--color-marca-200)" />
        <path d="M0 64h108l-8 12H8z" fill="var(--color-tinta)" />
      </g>
      {/* celular */}
      <g transform="translate(352 60)">
        <rect width="54" height="94" rx="10" fill="#fff" stroke="var(--color-tinta)" strokeWidth="5" />
        <rect x="9" y="12" width="36" height="58" rx="3" fill="var(--color-marca-200)" />
        <circle cx="27" cy="81" r="4" fill="var(--color-tinta)" />
      </g>
      {/* monitor */}
      <g transform="translate(310 250)">
        <rect width="110" height="72" rx="6" fill="#fff" stroke="var(--color-tinta)" strokeWidth="5" />
        <rect x="10" y="10" width="90" height="52" rx="2" fill="var(--color-marca-100)" />
        <path d="M45 72h20l6 18H39z" fill="var(--color-tinta)" />
        <path d="M30 92h50" stroke="var(--color-tinta)" strokeWidth="5" strokeLinecap="round" />
      </g>
      {/* batería */}
      <g transform="translate(150 48)">
        <rect width="70" height="36" rx="6" fill="#fff" stroke="var(--color-tinta)" strokeWidth="5" />
        <rect x="70" y="11" width="8" height="14" rx="2" fill="var(--color-tinta)" />
        <rect x="8" y="8" width="34" height="20" rx="2" fill="#f59e0b" />
      </g>
    </svg>
  );
}

/** Pequeñas ilustraciones para tarjetas temáticas. */
export function IlustracionReparar({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 90" className={className} aria-hidden>
      <rect x="10" y="12" width="78" height="50" rx="5" fill="var(--color-marca-100)" stroke="var(--color-tinta)" strokeWidth="4" />
      <path d="M4 68h90l-6 10H10z" fill="var(--color-tinta)" />
      <g transform="rotate(-35 92 40)">
        <rect x="86" y="18" width="12" height="52" rx="4" fill="var(--color-marca-600)" />
        <circle cx="92" cy="18" r="13" fill="var(--color-marca-600)" />
        <circle cx="92" cy="14" r="6" fill="var(--color-marca-100)" />
      </g>
    </svg>
  );
}

export function IlustracionEntregar({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 90" className={className} aria-hidden>
      <rect x="8" y="30" width="62" height="40" rx="4" fill="var(--color-tierra-200)" stroke="var(--color-tinta)" strokeWidth="4" />
      <path d="M8 42h62M39 30v40" stroke="var(--color-tinta)" strokeWidth="3" />
      <path d="M70 40h26l16 16v14H70z" fill="var(--color-marca-600)" stroke="var(--color-tinta)" strokeWidth="4" strokeLinejoin="round" />
      <circle cx="28" cy="76" r="8" fill="var(--color-tinta)" />
      <circle cx="92" cy="76" r="8" fill="var(--color-tinta)" />
      <path d="M80 12c0-8 6-12 16-12-1 10-7 14-15 14" fill="var(--color-marca-500)" />
    </svg>
  );
}

export function IlustracionCuidar({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 90" className={className} aria-hidden>
      <path d="M60 82S14 58 14 30a22 22 0 0 1 46-8 22 22 0 0 1 46 8c0 28-46 52-46 52z" fill="var(--color-marca-200)" stroke="var(--color-tinta)" strokeWidth="4" />
      <rect x="42" y="30" width="36" height="24" rx="3" fill="#fff" stroke="var(--color-tinta)" strokeWidth="4" />
      <path d="M38 58h44" stroke="var(--color-tinta)" strokeWidth="4" strokeLinecap="round" />
    </svg>
  );
}
