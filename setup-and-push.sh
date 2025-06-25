#!/bin/bash
# Este script inicializará Git y subirá tu proyecto a GitHub.

# --- ¡ACCIÓN REQUERIDA! ---
# Pega la URL de tu repositorio de GitHub aquí abajo, entre las comillas.
# Debe lucir como: https://github.com/tu-usuario/tu-repositorio.git
GITHUB_URL="PEGA_AQUI_LA_URL_DE_TU_REPOSITORIO_DE_GITHUB"

# --- Fin de la acción requerida ---

# Validar que la URL fue cambiada
if [ "$GITHUB_URL" == "PEGA_AQUI_LA_URL_DE_TU_REPOSITORIO_DE_GITHUB" ]; then
  echo "------------------------------------------------------------------"
  echo "¡ERROR! Por favor, edita este script (setup-and-push.sh) y"
  echo "reemplaza 'PEGA_AQUI_LA_URL_DE_TU_REPOSITORIO_DE_GITHUB' con la URL"
  echo "real de tu repositorio de GitHub antes de ejecutarlo."
  echo "------------------------------------------------------------------"
  exit 1
fi

echo "Paso 1: Inicializando el repositorio de Git..."
git init

echo "Paso 2: Configurando la rama principal como 'main'..."
git branch -M main

echo "Paso 3: Agregando todos los archivos al repositorio..."
git add .

echo "Paso 4: Creando el primer commit..."
git commit -m "Initial commit from Firebase Studio"

echo "Paso 5: Conectando con el repositorio remoto de GitHub..."
git remote add origin $GITHUB_URL

echo "Paso 6: Verificando la conexión remota..."
git remote -v

echo "Paso 7: Subiendo el código a la rama 'main' en GitHub..."
git push -u origin main

echo "------------------------------------------------------------------"
echo "¡ÉXITO! Tu código ha sido publicado en GitHub."
echo "Ahora puedes clonarlo en tu computadora local con el comando:"
echo "git clone $GITHUB_URL"
echo "------------------------------------------------------------------"
