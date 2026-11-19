#!/usr/bin/env bash
# One-time setup of the HOSTKEY VPS for WrestlingStory (JF-001, lot 3). See docs/decisions/D-002-hebergement.md.
#
# Run by Julien, as the "deploy" user, from his own terminal (sudo asks for his password):
#   scp -r deploy deploy@82.25.58.90:~/wrestlingstory-setup
#   ssh -t deploy@82.25.58.90 'bash ~/wrestlingstory-setup/setup-vps.sh'
#
# Safe to run again: every step checks what already exists. ChatPulse (Nginx default site, pm2, its
# PostgreSQL 16 container on 127.0.0.1:5432) is never modified.
# No secret is printed: the database password and APP_KEY are generated straight into shared/.env.
set -euo pipefail

HOST_NAME="wrestlingstory.82-25-58-90.sslip.io"
APP_DIR="/var/www/wrestlingstory"
SHARED="$APP_DIR/shared"
SETUP_DIR="$(cd "$(dirname "$0")" && pwd)"
DB_CONTAINER="wrestlingstory_postgres"
DB_PORT="5433"
DEPLOY_KEY="$HOME/.ssh/wrestlingstory_deploy"

step() { printf '\n==> %s\n' "$1"; }

if [ "$(id -un)" != "deploy" ]; then
    echo "Lance ce script en tant que deploy." >&2
    exit 1
fi

step "Droits administrateur (ton mot de passe sudo)"
sudo -v

step "Swap de 2 Go (le VPS n'a que 1,8 Go de RAM)"
if ! swapon --show | grep -q '/swapfile'; then
    sudo fallocate -l 2G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile >/dev/null
    sudo swapon /swapfile
    grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
fi
swapon --show

step "PHP 8.4-FPM (dépôt ondrej/php : Ubuntu 24.04 n'a que PHP 8.3)"
if ! command -v php8.4 >/dev/null; then
    sudo apt-get update -q
    sudo apt-get install -y -q software-properties-common
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update -q
fi
sudo apt-get install -y -q php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-intl php8.4-zip php8.4-bcmath php8.4-opcache
php8.4 -v | head -1

step "Pool PHP-FPM wrestlingstory (utilisateur deploy)"
sudo install -m 644 "$SETUP_DIR/php-fpm/wrestlingstory.conf" /etc/php/8.4/fpm/pool.d/wrestlingstory.conf
# The default "www" pool is not used: disable it to save memory.
if [ -f /etc/php/8.4/fpm/pool.d/www.conf ]; then
    sudo mv /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.disabled
fi
sudo php-fpm8.4 -t
sudo systemctl restart php8.4-fpm

step "Dossiers $APP_DIR (releases, shared, current)"
sudo mkdir -p "$APP_DIR"
sudo chown deploy:deploy "$APP_DIR"
mkdir -p "$APP_DIR/releases" \
    "$SHARED/storage/app/public" "$SHARED/storage/app/private" \
    "$SHARED/storage/framework/cache/data" "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" "$SHARED/storage/logs"

step "Fichier .env de production (créé une seule fois, secrets générés sans être affichés)"
if [ ! -f "$SHARED/.env" ]; then
    umask 077
    DB_PASSWORD="$(openssl rand -hex 24)"
    APP_KEY="base64:$(openssl rand -base64 32)"
    cat > "$SHARED/.env" <<EOF
APP_NAME=WrestlingStory
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=https://$HOST_NAME

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=$DB_PORT
DB_DATABASE=wrestlingstory
DB_USERNAME=wrestlingstory
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=log

INERTIA_SSR_ENABLED=false

# Later (Julien): OPENROUTER_API_KEY=
EOF
    unset DB_PASSWORD APP_KEY
    umask 022
fi
chmod 600 "$SHARED/.env"
echo "OK : $SHARED/.env (contenu non affiché)"

step "PostgreSQL 17 dans Docker sur 127.0.0.1:$DB_PORT (celui de ChatPulse n'est pas touché)"
if ! docker ps -a --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
    ENV_FILE="$(mktemp)"
    chmod 600 "$ENV_FILE"
    {
        echo "POSTGRES_USER=wrestlingstory"
        echo "POSTGRES_DB=wrestlingstory"
        grep '^DB_PASSWORD=' "$SHARED/.env" | sed 's/^DB_PASSWORD=/POSTGRES_PASSWORD=/'
    } > "$ENV_FILE"
    docker run -d --name "$DB_CONTAINER" --restart unless-stopped \
        -p "127.0.0.1:$DB_PORT:5432" --env-file "$ENV_FILE" \
        -v wrestlingstory_pgdata:/var/lib/postgresql/data postgres:17 >/dev/null
    rm -f "$ENV_FILE"
fi
for _ in $(seq 1 30); do
    docker exec "$DB_CONTAINER" pg_isready -U wrestlingstory -d wrestlingstory >/dev/null 2>&1 && break
    sleep 2
done
docker ps --filter "name=$DB_CONTAINER" --format '{{.Names}}  {{.Status}}  {{.Ports}}'

step "Site Nginx $HOST_NAME (ChatPulse garde le site par défaut)"
if [ ! -f /etc/nginx/sites-available/wrestlingstory ]; then
    sudo install -m 644 "$SETUP_DIR/nginx/wrestlingstory.conf" /etc/nginx/sites-available/wrestlingstory
fi
sudo ln -sfn /etc/nginx/sites-available/wrestlingstory /etc/nginx/sites-enabled/wrestlingstory
# Placeholder page until the first deployment, so that certbot and the first check have something to serve.
if [ ! -e "$APP_DIR/current" ]; then
    mkdir -p "$APP_DIR/releases/placeholder/public"
    echo '<!doctype html><title>WrestlingStory</title><p>Bientôt en ligne.</p>' > "$APP_DIR/releases/placeholder/public/index.html"
    ln -sfn "$APP_DIR/releases/placeholder" "$APP_DIR/current"
fi
sudo nginx -t
sudo systemctl reload nginx

step "Pare-feu (ufw) : HTTP et HTTPS"
if sudo ufw status | grep -q 'Status: active'; then
    sudo ufw allow 'Nginx Full'
    sudo ufw status | grep -E 'Nginx|443|80' || true
else
    echo "ufw inactif : rien à ouvrir côté VPS (vérifie aussi le pare-feu du panneau HOSTKEY)."
fi

step "Certificat HTTPS Let's Encrypt pour $HOST_NAME"
echo "En continuant, tu acceptes les conditions de Let's Encrypt : https://letsencrypt.org/repository/"
sudo apt-get install -y -q certbot python3-certbot-nginx
if ! sudo test -d "/etc/letsencrypt/live/$HOST_NAME"; then
    sudo certbot --nginx -d "$HOST_NAME" --non-interactive --agree-tos \
        --register-unsafely-without-email --redirect
fi
sudo grep -q 'Strict-Transport-Security' /etc/nginx/sites-available/wrestlingstory || \
    sudo sed -i '/listen 443 ssl/a\    add_header Strict-Transport-Security "max-age=31536000" always;' /etc/nginx/sites-available/wrestlingstory
sudo nginx -t
sudo systemctl reload nginx

step "deploy peut recharger PHP-FPM sans mot de passe (et rien d'autre)"
SUDOERS_TMP="$(mktemp)"
echo 'deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm' > "$SUDOERS_TMP"
sudo visudo -cf "$SUDOERS_TMP" >/dev/null
sudo install -m 440 -o root -g root "$SUDOERS_TMP" /etc/sudoers.d/wrestlingstory
rm -f "$SUDOERS_TMP"
sudo -k
sudo -n /usr/bin/systemctl reload php8.4-fpm && echo "OK : reload sans mot de passe"

step "Clé SSH dédiée au déploiement par GitHub Actions"
mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
if [ ! -f "$DEPLOY_KEY" ]; then
    ssh-keygen -q -t ed25519 -N '' -C 'github-actions-wrestlingstory' -f "$DEPLOY_KEY"
fi
touch "$HOME/.ssh/authorized_keys"
chmod 600 "$HOME/.ssh/authorized_keys"
grep -qF "$(cut -d' ' -f2 "$DEPLOY_KEY.pub")" "$HOME/.ssh/authorized_keys" || cat "$DEPLOY_KEY.pub" >> "$HOME/.ssh/authorized_keys"

step "Vérification"
curl -sS -o /dev/null -w "https://$HOST_NAME -> HTTP %{http_code}\n" "https://$HOST_NAME/" || true
curl -sS -o /dev/null -w "http://82.25.58.90 (ChatPulse) -> HTTP %{http_code}\n" "http://127.0.0.1/" || true

cat <<EOF

Terminé. Dernière étape, à faire toi-même :
  1. Affiche la clé privée de déploiement :   cat $DEPLOY_KEY
  2. Sur GitHub : Settings -> Secrets and variables -> Actions -> New repository secret
     Nom : VPS_SSH_KEY   Valeur : tout le bloc affiché (lignes BEGIN et END comprises).
Ne colle cette clé nulle part ailleurs.
EOF
