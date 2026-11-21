#!/usr/bin/env bash
# Connects the production backend to the Hostinger mailbox so it can send
# e-mails (new quote request, new contact message...). Run it once, from a
# terminal (the mailbox password is typed in, never stored in the repo):
#
#   ssh -t laperledor@54.39.230.49 'bash /var/www/laperledor/deploy/06-configure-mail.sh'
#
# It can be re-run to change the password or the address. The pre-production
# copy keeps MAIL_MAILER=log: it never sends real e-mails.

set -euo pipefail

APP_DIR="/var/www/laperledor/backend"
DEFAULT_ADDRESS="contact@laperledor-togo.com"

cd "$APP_DIR"

read -r -p "Adresse e-mail d'envoi [$DEFAULT_ADDRESS] : " MAIL_ADDRESS
MAIL_ADDRESS="${MAIL_ADDRESS:-$DEFAULT_ADDRESS}"
read -r -s -p "Mot de passe de cette boîte mail (rien ne s'affiche, c'est normal) : " MAIL_PASSWORD
echo
if [ -z "$MAIL_PASSWORD" ]; then
    echo "!! Mot de passe vide, rien n'a été changé." >&2
    exit 1
fi

echo "==> Configuration (.env)"
cp .env ".env.backup-$(date +%Y%m%d-%H%M%S)"
MAIL_ADDRESS="$MAIL_ADDRESS" MAIL_PASSWORD="$MAIL_PASSWORD" php -r '
$path = ".env";
$env = file_get_contents($path);
$quote = fn (string $v) => "\"".addcslashes($v, "\"\\$")."\"";
$set = function (string $key, string $value) use (&$env) {
    $line = $key."=".$value;
    $pattern = "/^".preg_quote($key, "/")."=.*/m";
    $env = preg_match($pattern, $env)
        ? preg_replace_callback($pattern, fn () => $line, $env)
        : rtrim($env)."\n".$line."\n";
};
$set("MAIL_MAILER", "smtp");
$set("MAIL_SCHEME", "smtps");
$set("MAIL_HOST", "smtp.hostinger.com");
$set("MAIL_PORT", "465");
$set("MAIL_USERNAME", getenv("MAIL_ADDRESS"));
$set("MAIL_PASSWORD", $quote(getenv("MAIL_PASSWORD")));
$set("MAIL_FROM_ADDRESS", getenv("MAIL_ADDRESS"));
$set("MAIL_FROM_NAME", $quote("La Perle d'"'"'Or"));
file_put_contents($path, $env);
'

php artisan config:cache >/dev/null
sudo systemctl restart laperledor-horizon

echo "==> Test : envoi d'un e-mail à $MAIL_ADDRESS"
if MAIL_ADDRESS="$MAIL_ADDRESS" php artisan tinker --execute='
    Illuminate\Support\Facades\Mail::raw(
        "Bonjour,\n\nCet e-mail confirme que le site La Perle d'"'"'Or peut envoyer des e-mails.\n\nVous recevrez désormais un e-mail à chaque nouvelle demande de devis et à chaque message du formulaire de contact.",
        fn ($m) => $m->to(getenv("MAIL_ADDRESS"))->subject("Test d'"'"'envoi : le site La Perle d'"'"'Or est connecté")
    );
    echo "OK\n";
' 2>&1 | grep -q "^OK"; then
    echo "E-mail de test envoyé : vérifiez la boîte de réception de $MAIL_ADDRESS (et les spams)."
else
    echo "!! L'envoi a échoué. Vérifiez le mot de passe puis relancez ce script." >&2
    echo "!! Détail : tail -n 30 $APP_DIR/storage/logs/laravel.log" >&2
    exit 1
fi
