#!/bin/bash
# Este script soluciona un problema común en algunos VPS donde Docker no puede iniciarse
# debido a un conflicto con la activación de sockets de systemd.

echo "--- Iniciando la reparación de Docker ---"

# Paso 1: Crear el archivo de configuración de Docker si no existe
echo "-> Configurando el daemon de Docker..."
mkdir -p /etc/docker
cat > /etc/docker/daemon.json <<EOF
{
  "exec-opts": ["native.cgroupdriver=systemd"],
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m"
  },
  "storage-driver": "overlay2"
}
EOF

# Paso 2: Crear un override para el servicio de systemd de Docker
echo "-> Creando override para el servicio de systemd de Docker..."
mkdir -p /etc/systemd/system/docker.service.d
cat > /etc/systemd/system/docker.service.d/override.conf <<EOF
[Service]
ExecStart=
ExecStart=/usr/bin/dockerd -H unix:///var/run/docker.sock
EOF

# Paso 3: Recargar la configuración de systemd y reiniciar Docker
echo "-> Recargando la configuración de systemd y reiniciando Docker..."
systemctl daemon-reload
systemctl restart docker

# Paso 4: Verificar el estado de Docker
echo "-> Verificando el estado del servicio Docker..."
systemctl status docker --no-pager

echo "--- Reparación de Docker completada ---"
echo "Si el estado de Docker es 'active (running)', el problema se ha solucionado."
