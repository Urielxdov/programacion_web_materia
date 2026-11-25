#!/bin/sh
# Arranque del contenedor backend:
#  - las migraciones se ejecutan solas al iniciar Nest (migrationsRun: true);
#  - el seed carga los datos de demostración solo si la base está vacía.
set -e

if [ "${SEED_ON_START:-true}" = "true" ]; then
  node dist/database/seeds/seed.js || echo "AVISO: el seed no se pudo ejecutar."
fi

exec node dist/main
