# Intercity237

**Plateforme de réservation de tickets de bus interurbains au Cameroun**  
Architecture Microservices + Event-Driven | SEN3244 Software Architecture — Spring 2026

---

## Problème résolu

Les voyageurs camerounais achètent leurs tickets de bus en agence physique : files d'attente, risques de perte de billet, pas de visibilité sur les places disponibles. Intercity237 digitalise la réservation de bus intercités avec paiement Mobile Money (MTN / Orange), ticket QR code instantané et gestion complète pour les opérateurs.

---

## Architecture

**Style** : Microservices + Event-Driven Architecture (EDA)

```
Internet → API Gateway (Nginx:80) → 5 Microservices PHP
                                           ↓
                                    RabbitMQ (Events)
                                           ↓
                               notification-service (Consumer)
```

| Service                | Port | Rôle                                          |
|------------------------|------|-----------------------------------------------|
| `api-gateway`          | 80   | Routage Nginx, reverse proxy                  |
| `auth-service`         | 8001 | Authentification, JWT, RBAC (4 rôles)         |
| `passenger-service`    | 8002 | Gestion passagers, panel admin                |
| `route-service`        | 8003 | Villes, routes, horaires — REST API JSON      |
| `booking-service`      | 8004 | Réservations, paiement fake MoMo, QR token    |
| `ticket-service`       | 8005 | Affichage et scan de tickets QR               |
| `notification-service` | —    | Consumer RabbitMQ (SMS/email simulé)          |
| `rabbitmq`             | 5672 | Message broker, échanges asynchrones          |

---

## Stack Technologique

| Couche           | Technologie                              |
|------------------|------------------------------------------|
| Backend          | PHP 8.2 + PDO                            |
| Base de données  | MariaDB 11 (`intercity237`)              |
| Message broker   | RabbitMQ 3.12 (AMQP)                     |
| Conteneurisation | Docker + Docker Compose                  |
| Orchestration    | Kubernetes k3s (rolling update)          |
| CI/CD            | Jenkins (5 stages)                       |
| Monitoring       | Prometheus + Grafana + Node Exporter     |
| IaC              | Ansible (install + deploy playbooks)     |
| Web server       | Apache (services) + Nginx (gateway)      |
| Paiement         | Simulation MTN Mobile Money / Orange Money|
| QR Code          | api.qrserver.com                         |

---

## Prérequis

- Docker 24+ et Docker Compose 2+
- PHP 8.2+ et Composer (pour les tests locaux)
- kubectl (pour Kubernetes)

---

## Lancement local (Docker Compose)

```bash
git clone https://github.com/RhaB17369/intercity237.git
cd intercity237

docker-compose up -d --build

docker-compose ps
# Application  : http://localhost
# RabbitMQ UI  : http://localhost:15672  (guest / guest)
# Grafana      : http://localhost:3000   (admin / admin123)
# Prometheus   : http://localhost:9090
```

**Compte superadmin** : `superadmin` / `Admin@1234`

---

## Flux de réservation

```
1. Accueil → sélectionner ville départ / arrivée / date
2. Choisir un voyage parmi les résultats (opérateur, heure, prix FCFA)
3. Saisir nom + téléphone + numéro MTN/Orange Money
4. Clic "Confirmer & Payer" → simulation paiement (~0.8s)
5. Redirection automatique vers le ticket QR
6. Ticket imprimable / téléchargeable (print CSS)
```

> Paiement toujours accepté sauf numéro commençant par `000` (cas d'erreur de démo).

---

## API REST (route-service)

```bash
# Santé
curl http://localhost/api/health

# Villes disponibles
curl http://localhost/api/cities

# Routes avec tarifs
curl http://localhost/api/routes

# Voyages disponibles (filtre optionnel)
curl "http://localhost/api/schedules?origin=1&destination=3&date=2026-06-10"

# Détail d'un voyage
curl http://localhost/api/schedules/5
```

---

## Exécuter les tests (PHPUnit)

```bash
cd auth-service
composer install
./vendor/bin/phpunit --testdox

# Rapport de couverture HTML
./vendor/bin/phpunit --coverage-html coverage/
# Ouvrir coverage/index.html
```

22 tests unitaires couvrant : XSS escaping `h()`, `is_logged_in()`, `is_admin()`, `is_superadmin()`, `csrf_token()`.

---

## Déploiement Kubernetes (k3s)

```bash
# Installer l'environnement via Ansible
ansible-playbook -i ansible/inventory.ini ansible/playbook-install.yml

# Appliquer tous les manifests
kubectl apply -f k8s/ --recursive
kubectl get pods -n intercity237

# Rollout status
kubectl rollout status deployment/booking-service -n intercity237

# Logs
kubectl logs -f deployment/booking-service -n intercity237
```

---

## CI/CD Jenkins (Jenkinsfile)

| Stage         | Action                                              |
|---------------|-----------------------------------------------------|
| Checkout      | `git checkout`                                      |
| Build         | `docker build` pour chaque service                  |
| Test          | `phpunit` dans auth-service                         |
| Push          | Push images sur GHCR (branche `main` uniquement)    |
| Deploy        | `kubectl apply` + rollback auto si échec            |

```bash
docker run -d -p 8090:8080 -v jenkins_home:/var/jenkins_home jenkins/jenkins:lts
# Credentials : ghcr-token + k3s-kubeconfig
# Créer un Pipeline Job pointant sur ce dépôt
```

---

## Monitoring

```bash
# Grafana : http://localhost:3000
# Datasource Prometheus : http://prometheus:9090
# Dashboard Node Exporter Full (ID 1860)
```

Métriques : CPU, RAM, requêtes HTTP, latence, file RabbitMQ.

---

## Infrastructure as Code (Ansible)

```bash
ansible-playbook -i ansible/inventory.ini ansible/playbook-install.yml   # setup VPS
ansible-playbook -i ansible/inventory.ini ansible/playbook-deploy.yml    # déploiement
```

---

## Structure du projet

```
intercity237/
├── auth-service/           ← Auth, JWT, RBAC — PHPUnit 22 tests
├── passenger-service/      ← Gestion passagers, admin panel
├── route-service/          ← Villes, routes, horaires — REST API
├── booking-service/        ← Réservations + paiement MoMo simulé
├── ticket-service/         ← Ticket QR code premium
├── notification-service/   ← Consumer RabbitMQ
├── api-gateway/            ← Nginx reverse proxy
├── k8s/                    ← Manifests Kubernetes
├── ansible/                ← Playbooks IaC
├── monitoring/             ← Config Prometheus
├── db/                     ← Schema SQL (intercity237.sql)
├── docs/                   ← SCRUM.md, USER_MANUAL.md
├── docker-compose.yml
├── Jenkinsfile
└── README.md
```

---

## Données de démo (SQL)

- **10 villes** : Yaoundé, Douala, Bafoussam, Garoua, Ngaoundéré, Bamenda, Bertoua, Ebolowa, Kumba, Limbe
- **4 opérateurs** : Buca Voyages, Guaranti Express, Vatican Express, Touristique Express
- **6 routes** avec prix FCFA (ex: Yaoundé→Douala 4 000 FCFA, Yaoundé→Bafoussam 3 500 FCFA)
- **Superadmin** : `superadmin` / `Admin@1234`

---

## Équipe

| Nom | Matricule | Rôle |
|-----|-----------|------|
|     |           |      |

**Cours** : SEN3244 Software Architecture  
**Instructeur** : Engr. TEKOH PALMA  
**Institution** : The ICT University — Faculty of ICT

---

*Intercity237 — Voyagez partout au Cameroun. Spring 2026*
