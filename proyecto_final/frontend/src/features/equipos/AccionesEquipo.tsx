import { Lightbulb, Loader2 } from 'lucide-react';
import { useEffect } from 'react';
import { ApiError } from '../../api/cliente';
import {
  useGestores,
  useRegistrarDecision,
  useRegistrarDiagnostico,
  useRegistrarReparacion,
  useSimularDiagnostico,
} from '../../api/hooks';
import type { DatosDecision } from '../../api/hooks';
import type { EquipoDetalle } from '../../api/tipos';
import { Boton } from '../../components/ui/Boton';
import { CampoArea, CampoSelect, CampoTexto, Casilla, OpcionesTarjeta } from '../../components/ui/Campo';
import { InsigniaCondicion } from '../../components/ui/Insignia';
import { Modal } from '../../components/ui/Modal';
import { useRetraso } from '../../hooks/dom';
import { useFormulario, v } from '../../hooks/useFormulario';
import { hoyISO } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

interface PropsModal {
  equipo: EquipoDetalle;
  abierto: boolean;
  alCerrar: () => void;
}

function Pie({ alCerrar, cargando, texto }: { alCerrar: () => void; cargando: boolean; texto: string }) {
  return (
    <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-5 sm:flex-row sm:justify-end">
      <Boton variante="secundario" onClick={alCerrar}>
        Cancelar
      </Boton>
      <Boton type="submit" cargando={cargando}>
        {texto}
      </Boton>
    </div>
  );
}

const errorApi = (titulo: string) => (e: unknown) => avisar.error(titulo, (e as ApiError).mensajes?.join(' ') ?? String(e));

// ───────────── Diagnóstico ─────────────

export function ModalDiagnostico({ equipo, abierto, alCerrar }: PropsModal) {
  const registrar = useRegistrarDiagnostico(equipo.id);
  const simular = useSimularDiagnostico(equipo.id);
  const form = useFormulario(
    { descripcion: '', enciende: '' as '' | 'si' | 'no', danoEstructural: false, componentesPeligrosos: false, costo: '0' },
    {
      descripcion: [v.requerido('Describe lo que encontraste.'), v.minimo(5)],
      enciende: [v.requerido('Indica si el equipo enciende.')],
      costo: [v.numeroMin(0, 'Escribe el costo estimado (0 si no necesita reparación).')],
    },
  );
  const { valores } = form;

  const datos = {
    descripcion: valores.descripcion.trim() || 'Vista previa',
    enciende: valores.enciende === 'si',
    danoEstructural: valores.danoEstructural,
    componentesPeligrosos: valores.componentesPeligrosos,
    costoReparacionEstimado: Number(valores.costo) || 0,
  };

  // Vista previa en vivo: se consulta al servidor la recomendación (patrón Strategy)
  // poco después de que la persona deja de modificar el formulario.
  const clave = useRetraso(JSON.stringify([valores.enciende, valores.danoEstructural, valores.componentesPeligrosos, valores.costo]), 400);
  useEffect(() => {
    if (!abierto || valores.enciende === '' || Number(valores.costo) < 0) return;
    simular.mutate(datos);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clave, abierto]);

  return (
    <Modal
      abierto={abierto}
      alCerrar={alCerrar}
      ancho="amplio"
      titulo="Registrar diagnóstico"
      descripcion={`${equipo.folio} · ${equipo.marca} ${equipo.modelo}`}
    >
      <form
        noValidate
        className="space-y-5"
        onSubmit={form.alEnviar(() =>
          registrar.mutate(
            { ...datos, descripcion: valores.descripcion.trim() },
            {
              onSuccess: (d) => {
                avisar.exito('Diagnóstico registrado', d.justificacion);
                alCerrar();
              },
              onError: errorApi('No se pudo registrar el diagnóstico'),
            },
          ),
        )}
      >
        <div ref={form.campo('enciende').ref} tabIndex={-1} className="focus:outline-none">
          <OpcionesTarjeta
            leyenda="¿El equipo enciende y funciona?"
            valor={valores.enciende}
            onChange={(val) => form.cambiar('enciende', val)}
            error={form.errores.enciende}
            opciones={[
              { valor: 'si', etiqueta: 'Sí, enciende' },
              { valor: 'no', etiqueta: 'No enciende o falla' },
            ]}
          />
        </div>
        <div className="grid gap-3 sm:grid-cols-2">
          <Casilla
            etiqueta="Tiene daño estructural grave"
            descripcion="Carcasa rota, placa quemada, pantalla estrellada."
            checked={valores.danoEstructural}
            onChange={(val) => form.cambiar('danoEstructural', val)}
          />
          <Casilla
            etiqueta="Tiene componentes peligrosos"
            descripcion="Batería inflada, fugas o piezas sueltas."
            checked={valores.componentesPeligrosos}
            onChange={(val) => form.cambiar('componentesPeligrosos', val)}
          />
        </div>
        <CampoTexto
          etiqueta="Costo estimado de reparación (MXN)"
          ayuda="Escribe 0 si no necesita reparación."
          obligatorio
          type="number"
          inputMode="decimal"
          min={0}
          step="0.01"
          value={valores.costo}
          onChange={(e) => form.cambiar('costo', e.target.value)}
          {...form.campo('costo')}
        />
        <CampoArea
          etiqueta="Descripción del diagnóstico"
          obligatorio
          rows={3}
          value={valores.descripcion}
          onChange={(e) => form.cambiar('descripcion', e.target.value)}
          {...form.campo('descripcion')}
        />

        <section aria-live="polite" className="rounded-2xl border-2 border-marca-200 bg-marca-50 p-5">
          <h3 className="mb-2 flex items-center gap-2 text-lg">
            <Lightbulb className="size-5 text-marca-700" aria-hidden /> Recomendación del sistema
          </h3>
          {valores.enciende === '' ? (
            <p className="text-tinta-suave">Responde si el equipo enciende para ver la recomendación.</p>
          ) : simular.isPending ? (
            <p className="flex items-center gap-2 text-tinta-suave">
              <Loader2 className="size-5 animate-spin" aria-hidden /> Calculando…
            </p>
          ) : simular.data ? (
            <div className="space-y-2">
              <InsigniaCondicion condicion={simular.data.condicion} />
              <p>{simular.data.justificacion}</p>
            </div>
          ) : null}
        </section>

        <Pie alCerrar={alCerrar} cargando={registrar.isPending} texto="Guardar diagnóstico" />
      </form>
    </Modal>
  );
}

// ───────────── Reparación ─────────────

export function ModalReparacion({ equipo, abierto, alCerrar }: PropsModal) {
  const registrar = useRegistrarReparacion(equipo.id);
  const form = useFormulario(
    { descripcion: '', costo: '', resultado: '' as '' | 'EXITOSA' | 'FALLIDA', fecha: hoyISO() },
    {
      resultado: [v.requerido('Indica si la reparación funcionó.')],
      costo: [v.numeroMin(0, 'Escribe el costo de la reparación.')],
      descripcion: [v.requerido('Describe qué se hizo.'), v.minimo(5)],
      fecha: [v.requerido('Indica la fecha.'), v.fechaNoFutura()],
    },
  );
  return (
    <Modal abierto={abierto} alCerrar={alCerrar} titulo="Registrar resultado de la reparación" descripcion={equipo.folio}>
      <form
        noValidate
        className="space-y-5"
        onSubmit={form.alEnviar((d) =>
          registrar.mutate(
            { descripcion: d.descripcion.trim(), costo: Number(d.costo), resultado: d.resultado as 'EXITOSA' | 'FALLIDA', fecha: d.fecha },
            {
              onSuccess: (r) => {
                avisar.exito(
                  r.resultado === 'EXITOSA' ? 'Reparación exitosa registrada' : 'Reparación sin éxito registrada',
                  r.resultado === 'EXITOSA' ? 'El equipo ya puede reasignarse.' : 'El equipo pasa a gestión especializada.',
                );
                alCerrar();
              },
              onError: errorApi('No se pudo registrar la reparación'),
            },
          ),
        )}
      >
        <div ref={form.campo('resultado').ref} tabIndex={-1} className="focus:outline-none">
          <OpcionesTarjeta
            leyenda="¿Cuál fue el resultado?"
            valor={form.valores.resultado}
            onChange={(val) => form.cambiar('resultado', val)}
            error={form.errores.resultado}
            opciones={[
              { valor: 'EXITOSA', etiqueta: 'Quedó funcionando', descripcion: 'Se podrá reasignar.' },
              { valor: 'FALLIDA', etiqueta: 'No se pudo reparar', descripcion: 'Irá a gestión especializada.' },
            ]}
          />
        </div>
        <div className="grid gap-5 sm:grid-cols-2">
          <CampoTexto
            etiqueta="Costo real (MXN)"
            obligatorio
            type="number"
            inputMode="decimal"
            min={0}
            step="0.01"
            value={form.valores.costo}
            onChange={(e) => form.cambiar('costo', e.target.value)}
            {...form.campo('costo')}
          />
          <CampoTexto
            etiqueta="Fecha"
            obligatorio
            type="date"
            max={hoyISO()}
            value={form.valores.fecha}
            onChange={(e) => form.cambiar('fecha', e.target.value)}
            {...form.campo('fecha')}
          />
        </div>
        <CampoArea
          etiqueta="¿Qué se hizo?"
          obligatorio
          rows={3}
          value={form.valores.descripcion}
          onChange={(e) => form.cambiar('descripcion', e.target.value)}
          {...form.campo('descripcion')}
        />
        <Pie alCerrar={alCerrar} cargando={registrar.isPending} texto="Guardar resultado" />
      </form>
    </Modal>
  );
}

// ───────────── Decisiones de destino ─────────────

type TipoDecisionModal = Exclude<DatosDecision['tipo'], 'ENVIO_REPARACION'>;

const TEXTOS: Record<TipoDecisionModal, { titulo: string; boton: string; destino?: string; ayudaDestino?: string }> = {
  REASIGNACION: {
    titulo: 'Reasignar equipo',
    boton: 'Confirmar reasignación',
    destino: 'Área o persona que lo recibe',
    ayudaDestino: 'Por ejemplo: Laboratorio de redes, edificio C.',
  },
  CONSERVACION: {
    titulo: 'Conservar como respaldo',
    boton: 'Confirmar',
    destino: 'Lugar donde se guardará',
    ayudaDestino: 'Por ejemplo: Almacén de equipos de respaldo.',
  },
  ENTREGA_GESTOR: { titulo: 'Entregar a gestor verificado', boton: 'Confirmar entrega' },
};

export function ModalDecision({ equipo, abierto, alCerrar, tipo }: PropsModal & { tipo: TipoDecisionModal }) {
  const decidir = useRegistrarDecision(equipo.id);
  const { data: gestores } = useGestores(true);
  const t = TEXTOS[tipo];
  const pideBorrado = equipo.requiereBorradoDatos && !equipo.datosBorrados && tipo !== 'CONSERVACION';
  const pideEvidencia = tipo !== 'CONSERVACION';

  const form = useFormulario(
    { destino: '', gestorId: '', evidencia: '', datosBorrados: false, notas: '' },
    {
      destino: t.destino ? [v.requerido('Indica el destino.'), v.minimo(3)] : [],
      gestorId: tipo === 'ENTREGA_GESTOR' ? [v.requerido('Selecciona el gestor que recibe el equipo.')] : [],
      evidencia: pideEvidencia ? [v.requerido('Escribe el folio u oficio que respalda el movimiento.'), v.minimo(3)] : [],
      datosBorrados: pideBorrado ? [(val) => (val ? null : 'Debes confirmar el borrado seguro de datos.')] : [],
      notas: [v.maximo(1000)],
    },
  );

  return (
    <Modal abierto={abierto} alCerrar={alCerrar} titulo={t.titulo} descripcion={`${equipo.folio} · ${equipo.marca} ${equipo.modelo}`}>
      <form
        noValidate
        className="space-y-5"
        onSubmit={form.alEnviar((d) =>
          decidir.mutate(
            {
              tipo,
              destino: d.destino.trim() || undefined,
              gestorId: d.gestorId ? Number(d.gestorId) : undefined,
              evidencia: d.evidencia.trim() || undefined,
              datosBorrados: d.datosBorrados,
              notas: d.notas.trim() || undefined,
            },
            {
              onSuccess: (m) => {
                avisar.exito('Movimiento registrado', m.descripcion);
                alCerrar();
              },
              onError: errorApi('No se pudo registrar el movimiento'),
            },
          ),
        )}
      >
        {t.destino && (
          <CampoTexto
            etiqueta={t.destino}
            ayuda={t.ayudaDestino}
            obligatorio
            value={form.valores.destino}
            onChange={(e) => form.cambiar('destino', e.target.value)}
            {...form.campo('destino')}
          />
        )}
        {tipo === 'ENTREGA_GESTOR' && (
          <CampoSelect
            etiqueta="Gestor que recibe"
            ayuda="Solo aparecen gestores con autorización verificada."
            obligatorio
            value={form.valores.gestorId}
            onChange={(e) => form.cambiar('gestorId', e.target.value)}
            {...form.campo('gestorId')}
          >
            <option value="">Selecciona un gestor…</option>
            {gestores?.map((g) => (
              <option key={g.id} value={g.id}>
                {g.nombre} ({g.documentoVerificacion})
              </option>
            ))}
          </CampoSelect>
        )}
        {pideEvidencia && (
          <CampoTexto
            etiqueta={tipo === 'ENTREGA_GESTOR' ? 'Folio del manifiesto o comprobante de entrega' : 'Oficio o folio de resguardo'}
            ayuda="Documento que respalda este movimiento (trazabilidad)."
            obligatorio
            value={form.valores.evidencia}
            onChange={(e) => form.cambiar('evidencia', e.target.value)}
            {...form.campo('evidencia')}
          />
        )}
        {pideBorrado && (
          <div className="space-y-1">
            <Casilla
              ref={form.campo('datosBorrados').ref}
              etiqueta="Confirmo que la información del equipo se borró de forma segura"
              descripcion="Formateo completo o restablecimiento de fábrica, y retiro de memorias o tarjetas SIM."
              checked={form.valores.datosBorrados}
              onChange={(val) => form.cambiar('datosBorrados', val)}
            />
            {form.errores.datosBorrados && (
              <p role="alert" className="text-sm font-bold text-red-700">
                {form.errores.datosBorrados}
              </p>
            )}
          </div>
        )}
        <CampoArea
          etiqueta="Notas"
          rows={2}
          value={form.valores.notas}
          onChange={(e) => form.cambiar('notas', e.target.value)}
          {...form.campo('notas')}
        />
        <Pie alCerrar={alCerrar} cargando={decidir.isPending} texto={t.boton} />
      </form>
    </Modal>
  );
}
