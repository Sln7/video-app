#!/usr/bin/env bash
# Exporta o certificado CA local do Caddy e exibe instruções
# de instalação por sistema operacional.
#
# Execute após o primeiro `sail up -d`:
#   ./trust-https.sh

set -e

CERT="caddy-root.crt"

echo "==> Aguardando o Caddy gerar a CA local..."
sleep 3

echo "==> Exportando certificado..."
docker compose exec caddy cat /data/caddy/pki/authorities/local/root.crt > "$CERT"
echo "    Salvo em: $(pwd)/$CERT"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Instale o certificado no seu sistema:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -qi microsoft /proc/version 2>/dev/null; then
    # WSL2 → instalar no Windows para Chrome/Edge funcionarem
    WIN_PATH=$(wslpath -w "$(pwd)/$CERT")
    echo "  WSL2 detectado. Para confiar no Chrome/Edge do Windows:"
    echo ""
    echo "    certutil.exe -addstore -f \"ROOT\" \"$WIN_PATH\""
    echo ""
    echo "  Para o Firefox no Windows (importar manualmente):"
    echo "    Configurações → Privacidade → Ver certificados → Importar → $WIN_PATH"
    echo ""
    echo "  Para browsers dentro do próprio WSL (Firefox Linux):"
    echo "    certutil -d sql:\$HOME/.pki/nssdb -A -t \"CT,,\" -n caddy-local -i $CERT"
else
    echo "  macOS:"
    echo "    sudo security add-trusted-cert -d -r trustRoot \\"
    echo "      -k /Library/Keychains/System.keychain $CERT"
    echo ""
    echo "  Linux (Debian/Ubuntu):"
    echo "    sudo cp $CERT /usr/local/share/ca-certificates/caddy.crt"
    echo "    sudo update-ca-certificates"
    echo ""
    echo "  Linux (Fedora/RHEL):"
    echo "    sudo cp $CERT /etc/pki/ca-trust/source/anchors/caddy.crt"
    echo "    sudo update-ca-trust"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Após instalar, acesse: https://localhost"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
