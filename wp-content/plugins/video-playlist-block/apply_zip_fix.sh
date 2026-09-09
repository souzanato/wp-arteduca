#!/usr/bin/env bash
set -euo pipefail

ZIP_NAME="video-playlist-block.zip"
EXCLUDES=(
  ".git"
  ".DS_Store"
)

# 1) Confere se está dentro de um repo git
REPO_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "${REPO_DIR}" ]]; then
  echo "ERRO: Execute este script dentro de um repositório git."
  exit 1
fi

# 2) Confere se está rodando na raiz do repo (por segurança)
PWD_REAL="$(pwd -P)"
if [[ "$PWD_REAL" != "$REPO_DIR" ]]; then
  echo "ERRO: Rode o script a partir da raiz do repo."
  echo "Atual: $PWD_REAL"
  echo "Repo:  $REPO_DIR"
  exit 1
fi

# ZIP fica sempre na pasta anterior
ZIP_PATH="${REPO_DIR}/../${ZIP_NAME}"
if [[ ! -f "$ZIP_PATH" ]]; then
  echo "ERRO: ZIP não encontrado em: $ZIP_PATH"
  echo "Coloque o arquivo ${ZIP_NAME} na pasta anterior (../) e rode novamente."
  exit 1
fi

# 3) Cria tmp e garante limpeza mesmo se der erro
TMP_DIR="$(mktemp -d /tmp/vpb-zipfix.XXXXXX)"
cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

echo "Repo: $REPO_DIR"
echo "ZIP:  $ZIP_PATH"
echo "TMP:  $TMP_DIR"
echo

echo "Extraindo ZIP para /tmp..."
unzip -q "$ZIP_PATH" -d "$TMP_DIR"

# 4) Detecta se o ZIP contém uma pasta "video-playlist-block/" ou se já vem "flat"
SRC_DIR="$TMP_DIR"
if [[ -d "$TMP_DIR/video-playlist-block" ]]; then
  SRC_DIR="$TMP_DIR/video-playlist-block"
fi

# 5) Prévia do que vai mudar
echo "Prévia do que vai mudar (dry-run):"
RSYNC_ARGS=(-ani --checksum)

for ex in "${EXCLUDES[@]}"; do
  RSYNC_ARGS+=("--exclude=${ex}")
done

# não deixa o script sumir e não mexe em zip acidentalmente se ele existir no repo
RSYNC_ARGS+=("--exclude=apply_zip_fix.sh")
RSYNC_ARGS+=("--exclude=${ZIP_NAME}")

rsync "${RSYNC_ARGS[@]}" "$SRC_DIR"/ "$REPO_DIR"/ | sed -n '1,200p'

echo
echo "Aplicando alterações (somente modificados/novos; sem deletar extras do repo)..."

RSYNC_APPLY_ARGS=(-ai --checksum)
for ex in "${EXCLUDES[@]}"; do
  RSYNC_APPLY_ARGS+=("--exclude=${ex}")
done
RSYNC_APPLY_ARGS+=("--exclude=apply_zip_fix.sh")
RSYNC_APPLY_ARGS+=("--exclude=${ZIP_NAME}")

rsync "${RSYNC_APPLY_ARGS[@]}" "$SRC_DIR"/ "$REPO_DIR"/

echo
echo "Limpeza: removendo tmp ($TMP_DIR)"
rm -rf "$TMP_DIR"
trap - EXIT

echo
echo "OK! Agora rode:"
echo "  git status"
echo "  git diff"
