#!/bin/sh
# Arranque del contenedor "citas": configura sendmail (msmtp) y lanza el servidor PHP.
# Si hay UNIDAD2_SMTP_PASS (viene de practicas_php/.env, que git ignora) envia con Gmail real;
# si no, usa equipo_1/msmtprc (entrega a Mailpit, sin salir a internet).
if [ -n "$UNIDAD2_SMTP_PASS" ] && [ -n "$UNIDAD2_SMTP_USER" ]; then
  PORT_SMTP="${UNIDAD2_SMTP_PORT:-587}"
  STARTTLS=on; [ "$PORT_SMTP" = "465" ] && STARTTLS=off
  cat > /etc/msmtprc <<CFG
defaults
auth on
tls on
tls_starttls $STARTTLS
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile /tmp/msmtp.log

account default
host ${UNIDAD2_SMTP_HOST:-smtp.gmail.com}
port $PORT_SMTP
user $UNIDAD2_SMTP_USER
password $UNIDAD2_SMTP_PASS
from ${UNIDAD2_MAIL_FROM:-$UNIDAD2_SMTP_USER}
CFG
  echo "[start.sh] sendmail: SMTP real (${UNIDAD2_SMTP_HOST:-smtp.gmail.com}) como $UNIDAD2_SMTP_USER"
else
  cp /app/equipo_1/msmtprc /etc/msmtprc
  echo "[start.sh] sendmail: Mailpit local (sin credenciales SMTP)"
fi
chmod 600 /etc/msmtprc
exec php -S 0.0.0.0:8080 -t /app
