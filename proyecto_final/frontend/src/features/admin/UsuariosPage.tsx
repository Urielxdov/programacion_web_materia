import { Pencil, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { ApiError } from '../../api/cliente';
import { useGuardarUsuario, useSesion, useUsuarios } from '../../api/hooks';
import type { Rol, Usuario } from '../../api/tipos';
import { Boton } from '../../components/ui/Boton';
import { CampoTexto, Casilla, OpcionesTarjeta } from '../../components/ui/Campo';
import { Cargando, ErrorCarga } from '../../components/ui/Estados';
import { Insignia } from '../../components/ui/Insignia';
import { Modal } from '../../components/ui/Modal';
import { EncabezadoPagina } from '../../components/ui/Pagina';
import { useFormulario, v } from '../../hooks/useFormulario';
import { ROL } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';

function FormularioUsuario({ usuario, alCerrar }: { usuario?: Usuario; alCerrar: () => void }) {
  const guardar = useGuardarUsuario();
  const form = useFormulario(
    {
      nombre: usuario?.nombre ?? '',
      email: usuario?.email ?? '',
      password: '',
      rol: (usuario?.rol ?? 'CAPTURISTA') as Rol,
      activo: usuario?.activo ?? true,
    },
    {
      nombre: [v.requerido('Escribe el nombre completo.'), v.minimo(3)],
      email: [v.requerido('Escribe el correo.'), v.email()],
      password: usuario ? [v.password()] : [v.requerido('Asigna una contraseña inicial.'), v.password()],
    },
  );
  return (
    <form
      noValidate
      className="space-y-5"
      onSubmit={form.alEnviar((d) =>
        guardar.mutate(
          {
            id: usuario?.id,
            nombre: d.nombre.trim(),
            email: d.email.trim(),
            rol: d.rol,
            ...(d.password ? { password: d.password } : {}),
            ...(usuario ? { activo: d.activo } : {}),
          },
          {
            onSuccess: () => {
              avisar.exito(usuario ? 'Usuario actualizado' : 'Usuario creado', d.email);
              alCerrar();
            },
            onError: (e) => avisar.error('No se pudo guardar el usuario', (e as ApiError).mensajes.join(' ')),
          },
        ),
      )}
    >
      <CampoTexto etiqueta="Nombre completo" obligatorio value={form.valores.nombre} onChange={(e) => form.cambiar('nombre', e.target.value)} {...form.campo('nombre')} />
      <CampoTexto etiqueta="Correo electrónico" obligatorio type="email" value={form.valores.email} onChange={(e) => form.cambiar('email', e.target.value)} {...form.campo('email')} />
      <CampoTexto
        etiqueta={usuario ? 'Nueva contraseña' : 'Contraseña inicial'}
        obligatorio={!usuario}
        ayuda={usuario ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres con letras y números. Entrégala de forma segura.'}
        type="password"
        autoComplete="new-password"
        value={form.valores.password}
        onChange={(e) => form.cambiar('password', e.target.value)}
        {...form.campo('password')}
      />
      <OpcionesTarjeta
        leyenda="Rol"
        valor={form.valores.rol}
        onChange={(r) => form.cambiar('rol', r)}
        columnas={3}
        opciones={(Object.keys(ROL) as Rol[]).map((r) => ({ valor: r, etiqueta: ROL[r].etiqueta, descripcion: ROL[r].descripcion }))}
      />
      {usuario && (
        <Casilla
          etiqueta="Cuenta activa"
          descripcion="Una cuenta desactivada no puede iniciar sesión."
          checked={form.valores.activo}
          onChange={(x) => form.cambiar('activo', x)}
        />
      )}
      <div className="flex flex-col-reverse gap-3 border-t border-tierra-200 pt-5 sm:flex-row sm:justify-end">
        <Boton variante="secundario" onClick={alCerrar}>
          Cancelar
        </Boton>
        <Boton type="submit" cargando={guardar.isPending}>
          Guardar
        </Boton>
      </div>
    </form>
  );
}

export function UsuariosPage() {
  const { data: yo } = useSesion();
  const { data, isLoading, error, refetch } = useUsuarios();
  const [editando, setEditando] = useState<Usuario | 'nuevo' | null>(null);

  return (
    <>
      <EncabezadoPagina
        titulo="Usuarios"
        descripcion="Cuentas del personal y su rol. Las contraseñas se guardan cifradas y nunca se muestran."
        migas={[{ etiqueta: 'Panel', a: '/panel' }, { etiqueta: 'Usuarios' }]}
        acciones={
          <Boton onClick={() => setEditando('nuevo')} icono={<UserPlus className="size-5" aria-hidden />}>
            Nuevo usuario
          </Boton>
        }
      />
      {isLoading && <Cargando />}
      {error && <ErrorCarga error={error} reintentar={() => refetch()} />}
      <ul className="grid gap-4 md:grid-cols-2">
        {data?.map((u) => (
          <li key={u.id} className="tarjeta flex items-start justify-between gap-4 p-5">
            <div className="space-y-1.5">
              <p className="text-lg font-bold">
                {u.nombre} {u.id === yo?.id && <span className="font-normal text-tinta-suave">(tú)</span>}
              </p>
              <p className="break-all text-tinta-suave">{u.email}</p>
              <div className="flex flex-wrap gap-2 pt-1">
                <Insignia tono={u.rol === 'ADMIN' ? 'morado' : u.rol === 'RESPONSABLE' ? 'exito' : 'info'}>{ROL[u.rol].etiqueta}</Insignia>
                {!u.activo && <Insignia tono="peligro">Desactivada</Insignia>}
              </div>
            </div>
            <Boton variante="fantasma" onClick={() => setEditando(u)} icono={<Pencil className="size-5" aria-hidden />}>
              Editar<span className="sr-only"> a {u.nombre}</span>
            </Boton>
          </li>
        ))}
      </ul>
      {editando && (
        <Modal abierto alCerrar={() => setEditando(null)} titulo={editando === 'nuevo' ? 'Nuevo usuario' : `Editar a ${editando.nombre}`}>
          <FormularioUsuario usuario={editando === 'nuevo' ? undefined : editando} alCerrar={() => setEditando(null)} />
        </Modal>
      )}
    </>
  );
}
