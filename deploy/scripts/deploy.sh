#!/usr/bin/env bash
# Rilis git HEAD ke staging: https://kyread-staging.skyvora.id
#
# Pemakaian (dari root repo, Git Bash / Linux / macOS):
#   deploy/scripts/deploy.sh                       # alias SSH "skyvora" (lihat ~/.ssh/config)
#   DEPLOY_HOST=root@1.2.3.4 deploy/scripts/deploy.sh
#
# Struktur di server:
#   /var/www/kyread-staging/releases/<rev>   kode; symlink "current" menunjuk ke rilis aktif
#   /var/www/kyread-staging/shared/          .env dan storage/ (dipakai lintas rilis)
#   /etc/kyread-staging/db.pass              password database
set -euo pipefail

HOST=${DEPLOY_HOST:-skyvora}
SITE_URL=https://kyread-staging.skyvora.id
REV=$(git rev-parse --short HEAD)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

if [ -n "$(git status --porcelain)" ]; then
  echo "Masih ada perubahan yang belum di-commit; commit dulu agar rilis sesuai revisi." >&2
  exit 1
fi

echo "==> Mengemas $REV"
git archive --format=tar.gz -o "$TMP/release.tgz" HEAD

echo "==> Mengunggah ke $HOST"
scp -q "$TMP/release.tgz" "$HOST:/tmp/kyread-staging-release.tgz"

echo "==> Mengaktifkan rilis"
ssh "$HOST" REV="$REV" bash -s <<'REMOTE'
set -euo pipefail
BASE=/var/www/kyread-staging
mkdir -p "$BASE/releases/$REV"
tar -xzf /tmp/kyread-staging-release.tgz -C "$BASE/releases/$REV" && rm -f /tmp/kyread-staging-release.tgz
echo "$REV" > "$BASE/releases/$REV/REVISION"
ln -sfn "$BASE/releases/$REV" "$BASE/current"

bash "$BASE/current/deploy/scripts/setup-server.sh"
systemctl reload php8.5-fpm

# Simpan 5 rilis terbaru.
ls -1dt "$BASE"/releases/*/ | tail -n +6 | xargs -r rm -rf
REMOTE

curl -fsS -o /dev/null -w "$SITE_URL -> %{http_code}\n" "$SITE_URL/"
