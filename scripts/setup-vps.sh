#!/bin/bash
# ============================================================
# Intercity237 HR Portal — VPS Setup Script
# Usage: bash setup-vps.sh
# ============================================================

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

log()  { echo -e "${GREEN}[Intercity237]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()  { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

log "=== Intercity237 VPS Setup Starting ==="

# Vérification root
[[ $EUID -ne 0 ]] && err "Ce script doit être exécuté en tant que root (sudo)"

# Mise à jour
log "Mise à jour du système..."
apt update && apt upgrade -y

# Dépendances
log "Installation des dépendances..."
apt install -y curl git nginx ufw apt-transport-https ca-certificates gnupg lsb-release

# Docker
log "Installation de Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker && systemctl start docker
    log "Docker installé: $(docker --version)"
else
    warn "Docker déjà installé: $(docker --version)"
fi

# Docker Compose
log "Installation de Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    COMPOSE_URL="https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64"
    curl -SL "$COMPOSE_URL" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    log "Docker Compose installé: $(docker-compose --version)"
fi

# k3s
log "Installation de k3s..."
if ! command -v k3s &> /dev/null; then
    curl -sfL https://get.k3s.io | sh -
    chmod 644 /etc/rancher/k3s/k3s.yaml
    log "k3s installé: $(k3s --version | head -1)"
else
    warn "k3s déjà installé"
fi

# Nginx
log "Configuration de Nginx..."
systemctl enable nginx && systemctl start nginx

# Firewall
log "Configuration du firewall UFW..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 15672/tcp  # RabbitMQ Management
ufw allow 30080/tcp  # NodePort K8s
echo "y" | ufw enable

# Jenkins
log "Démarrage de Jenkins en Docker..."
if ! docker ps -a | grep -q jenkins; then
    docker run -d \
      -p 8090:8080 \
      -p 50000:50000 \
      -v jenkins_home:/var/jenkins_home \
      -v /var/run/docker.sock:/var/run/docker.sock \
      --restart=always \
      --name jenkins \
      jenkins/jenkins:lts
    log "Jenkins démarré — accès: http://$(hostname -I | awk '{print $1}'):8090"
    log "Mot de passe initial Jenkins:"
    sleep 15
    docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword 2>/dev/null || warn "Attendre 30s et relancer: docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword"
else
    warn "Jenkins déjà démarré"
fi

log "=== Setup terminé ==="
log "VPS IP: $(hostname -I | awk '{print $1}')"
log "Application: http://$(hostname -I | awk '{print $1}')"
log "Jenkins: http://$(hostname -I | awk '{print $1}'):8090"
log "Prochaine étape: déployer avec Ansible"
log "  ansible-playbook -i ansible/inventory.ini ansible/playbook-deploy.yml"
