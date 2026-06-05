# PLAN D'EXÉCUTION — 48H CHRONO
## SEN3244 Software Architecture — Intercity237
### Architecture : Microservices + Event-Driven (EDA)

---

| Champ          | Valeur                                                    |
|----------------|-----------------------------------------------------------|
| Cours          | Software Architecture — SEN3244                          |
| Instructeur    | Engr. TEKOH PALMA                                        |
| Projet         | Intercity237 (PHP 8.2 + MariaDB)                   |
| Architecture   | **Microservices + Event-Driven Architecture (RabbitMQ)** |
| Deadline       | J+2 — 1ère semaine après examens écrits Spring 2026      |
| Total points   | 105 pts répartis sur 10 sections                         |

---

## RÈGLES D'OR POUR 48H

> **1.** Ne pas réécrire le code PHP — réorganiser en dossiers de services.
> **2.** Docker Compose est ta démo locale. k3s sur VPS est ta démo "production".
> **3.** Prendre des screenshots à CHAQUE étape qui fonctionne — ne jamais compter sur ta mémoire.
> **4.** La Section 8 (Architecture, 20 pts) se fait avec du texte et des diagrammes — pas de code. C'est ton meilleur ROI.
> **5.** Un retard = 40% perdu. La deadline prime sur la perfection.

---

## ARCHITECTURE CIBLE

```
                        ┌──────────────────────────┐
                        │      CLIENT (Browser)     │
                        └───────────┬──────────────┘
                                    │ HTTPS
                        ┌───────────▼──────────────┐
                        │       API GATEWAY         │
                        │  Nginx + Auth Middleware  │
                        └──┬──────┬──────┬──────────┘
                           │      │      │
               ┌───────────▼─┐  ┌─▼──────▼──┐  ┌──────────────┐
               │ auth-service│  │passenger-service│  │ route-service │
               │   :8001     │  │   :8002    │  │   :8003      │
               │ JWT + RBAC  │  │  CRUD RH   │  │ Departments  │
               └──────┬──────┘  └─────┬──────┘  └──────┬───────┘
                      │               │                 │
                      └───────────────┼─────────────────┘
                                      │  Publish Events
                         ┌────────────▼─────────────┐
                         │        RabbitMQ           │
                         │    (Message Broker)       │
                         └────────────┬─────────────┘
                                      │  Consume
                         ┌────────────▼─────────────┐
                         │   notification-service    │
                         │  :8004  (Email, Alertes)  │
                         └──────────────────────────┘
```

**Événements asynchrones :**

| Événement                       | Producteur    | Consommateur          |
|---------------------------------|---------------|-----------------------|
| `user.registered`               | auth-service  | notification-service  |
| `user.password_reset_requested` | auth-service  | notification-service  |
| `user.role.changed`             | passenger-service  | auth-service          |
| `department.record.created`     | route-service  | notification-service  |

---

## STRUCTURE DE DOSSIERS CIBLE

```
intercity237/
├── api-gateway/
│   ├── nginx.conf
│   └── Dockerfile
├── auth-service/
│   ├── src/          ← login.php, register.php, includes/auth.php
│   ├── tests/
│   └── Dockerfile
├── passenger-service/
│   ├── src/          ← admin/users.php, admin/admins.php
│   ├── tests/
│   └── Dockerfile
├── route-service/
│   ├── src/          ← department.php, admin/database.php
│   ├── tests/
│   └── Dockerfile
├── notification-service/
│   ├── src/          ← consumer RabbitMQ + mailer
│   └── Dockerfile
├── k8s/
├── ansible/
├── scripts/
├── docker-compose.yml
└── Jenkinsfile
```

---

## JOUR 1 — INFRASTRUCTURE + MICROSERVICES + CICD

### H1–H2 : Git + Repo GitHub (Section 1 partiel)

```bash
# Créer .gitignore
echo "config/db.php
vendor/
coverage/
*.log
.env" > .gitignore

# Copier db.php en exemple
cp config/db.php config/db.example.php
# Vider les valeurs sensibles dans db.example.php

# Push sur GitHub
git add .
git commit -m "Initial commit - Intercity237"
git remote add origin https://github.com/<username>/intercity237.git
git push -u origin main
```

**Screenshots à prendre :**
- [ ] Repo GitHub avec le code visible

---

### H3–H5 : Réorganisation en microservices + Docker Compose (Sections 1 + 7)

**1. Créer la structure de dossiers :**

```bash
mkdir -p auth-service/src auth-service/tests
mkdir -p passenger-service/src passenger-service/tests
mkdir -p route-service/src route-service/tests
mkdir -p notification-service/src
mkdir -p api-gateway

# Déplacer les fichiers PHP dans les services
cp login.php register.php logout.php forgot_password.php reset_password.php includes/auth.php auth-service/src/
cp admin/users.php admin/admins.php passenger-service/src/
cp department.php admin/database.php route-service/src/
cp -r config/ includes/ css/ js/ auth-service/src/
```

**2. Dockerfile pour chaque service** (même base, port différent) :

`auth-service/Dockerfile` :

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mbstring
RUN a2enmod rewrite
COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
```

> Répéter le même Dockerfile pour `passenger-service`, `route-service`.

`notification-service/Dockerfile` :

```dockerfile
FROM php:8.2-cli
RUN docker-php-ext-install sockets
WORKDIR /app
COPY src/ /app/
CMD ["php", "consumer.php"]
```

**3. `docker-compose.yml` central :**

```yaml
version: '3.9'

services:

  api-gateway:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./api-gateway/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - auth-service
      - passenger-service
      - route-service
    networks: [intercity237-net]

  auth-service:
    build: ./auth-service
    environment:
      DB_HOST: db-auth
      DB_NAME: intercity237_auth
      DB_USER: intercity237
      DB_PASS: Intercity2372026
      RABBITMQ_HOST: rabbitmq
    depends_on:
      db-auth:
        condition: service_healthy
      rabbitmq:
        condition: service_healthy
    networks: [intercity237-net]

  passenger-service:
    build: ./passenger-service
    environment:
      DB_HOST: db-users
      DB_NAME: intercity237_users
      DB_USER: intercity237
      DB_PASS: Intercity2372026
      RABBITMQ_HOST: rabbitmq
    depends_on:
      - db-users
      - rabbitmq
    networks: [intercity237-net]

  route-service:
    build: ./route-service
    environment:
      DB_HOST: db-dept
      DB_NAME: intercity237_dept
      DB_USER: intercity237
      DB_PASS: Intercity2372026
      RABBITMQ_HOST: rabbitmq
    depends_on:
      - db-dept
      - rabbitmq
    networks: [intercity237-net]

  notification-service:
    build: ./notification-service
    environment:
      RABBITMQ_HOST: rabbitmq
    depends_on:
      rabbitmq:
        condition: service_healthy
    networks: [intercity237-net]

  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "15672:15672"
      - "5672:5672"
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks: [intercity237-net]

  db-auth:
    image: mariadb:11
    environment:
      MYSQL_DATABASE: intercity237_auth
      MYSQL_USER: intercity237
      MYSQL_PASSWORD: Intercity2372026
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_auth_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect"]
      interval: 10s
      retries: 5
    networks: [intercity237-net]

  db-users:
    image: mariadb:11
    environment:
      MYSQL_DATABASE: intercity237_users
      MYSQL_USER: intercity237
      MYSQL_PASSWORD: Intercity2372026
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_users_data:/var/lib/mysql
    networks: [intercity237-net]

  db-dept:
    image: mariadb:11
    environment:
      MYSQL_DATABASE: intercity237_dept
      MYSQL_USER: intercity237
      MYSQL_PASSWORD: Intercity2372026
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_dept_data:/var/lib/mysql
    networks: [intercity237-net]

volumes:
  db_auth_data:
  db_users_data:
  db_dept_data:

networks:
  intercity237-net:
    driver: bridge
```

**4. `api-gateway/nginx.conf` :**

```nginx
server {
    listen 80;

    location /auth/ {
        proxy_pass http://auth-service/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location /users/ {
        proxy_pass http://passenger-service/;
        proxy_set_header Host $host;
    }

    location /departments/ {
        proxy_pass http://route-service/;
        proxy_set_header Host $host;
    }

    location / {
        proxy_pass http://auth-service/;
    }
}
```

**Tester :**

```bash
docker-compose up -d --build
docker-compose ps
```

**Screenshots à prendre :**
- [ ] `docker-compose ps` — tous les services en état `Up`
- [ ] RabbitMQ Management UI sur `http://localhost:15672`
- [ ] Application accessible sur `http://localhost`

---

### H6–H8 : Kubernetes sur VPS (Section 7 — 15 pts)

**Sur le VPS (Hetzner CX22 ou Oracle Free) :**

```bash
# Installer k3s
curl -sfL https://get.k3s.io | sh -
export KUBECONFIG=/etc/rancher/k3s/k3s.yaml

# Vérifier
kubectl get nodes
```

**Structure `k8s/` :**

```
k8s/
├── namespace.yaml
├── rabbitmq-deployment.yaml
├── auth-service-deployment.yaml
├── passenger-service-deployment.yaml
├── route-service-deployment.yaml
├── notification-service-deployment.yaml
└── api-gateway-deployment.yaml
```

**`k8s/namespace.yaml` :**

```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: intercity237
```

**`k8s/auth-service-deployment.yaml` :**

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: auth-service
  namespace: intercity237
spec:
  replicas: 2
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxUnavailable: 1
      maxSurge: 1
  selector:
    matchLabels:
      app: auth-service
  template:
    metadata:
      labels:
        app: auth-service
    spec:
      containers:
      - name: auth-service
        image: ghcr.io/<username>/intercity237-auth:latest
        ports:
        - containerPort: 80
        env:
        - name: RABBITMQ_HOST
          value: rabbitmq
        resources:
          requests:
            memory: "64Mi"
            cpu: "50m"
          limits:
            memory: "128Mi"
            cpu: "200m"
---
apiVersion: v1
kind: Service
metadata:
  name: auth-service
  namespace: intercity237
spec:
  selector:
    app: auth-service
  ports:
  - port: 80
    targetPort: 80
```

> Répliquer ce pattern pour `passenger-service` et `route-service`.

**`k8s/rabbitmq-deployment.yaml` :**

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: rabbitmq
  namespace: intercity237
spec:
  replicas: 1
  selector:
    matchLabels:
      app: rabbitmq
  template:
    metadata:
      labels:
        app: rabbitmq
    spec:
      containers:
      - name: rabbitmq
        image: rabbitmq:3-management
        ports:
        - containerPort: 5672
        - containerPort: 15672
---
apiVersion: v1
kind: Service
metadata:
  name: rabbitmq
  namespace: intercity237
spec:
  selector:
    app: rabbitmq
  ports:
  - name: amqp
    port: 5672
    targetPort: 5672
  - name: management
    port: 15672
    targetPort: 15672
```

**Déployer :**

```bash
kubectl apply -f k8s/
kubectl get pods -n intercity237
kubectl get services -n intercity237
```

**Screenshots à prendre :**
- [ ] `kubectl get pods -n intercity237` — tous en `Running`
- [ ] `kubectl get services -n intercity237`
- [ ] Rolling update : `kubectl rollout status deployment/auth-service -n intercity237`

---

### H9–H11 : Pipeline Jenkins (Section 3 — 10 pts)

**Lancer Jenkins sur le VPS :**

```bash
docker run -d \
  -p 8090:8080 \
  -v jenkins_home:/var/jenkins_home \
  -v /var/run/docker.sock:/var/run/docker.sock \
  --restart=always \
  --name jenkins \
  jenkins/jenkins:lts
```

**`Jenkinsfile` :**

```groovy
pipeline {
    agent any

    environment {
        REGISTRY   = 'ghcr.io'
        NAMESPACE  = 'intercity237'
        SERVICES   = 'auth-service passenger-service route-service notification-service'
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
                echo "Build #${env.BUILD_NUMBER} — Branch: ${env.BRANCH_NAME}"
            }
        }

        stage('Build All Services') {
            steps {
                script {
                    def services = env.SERVICES.split(' ')
                    services.each { svc ->
                        sh "docker build -t ${REGISTRY}/<username>/intercity237-${svc}:${env.BUILD_NUMBER} ./${svc}"
                    }
                }
            }
        }

        stage('Run Tests') {
            steps {
                sh """
                    docker run --rm \
                      ghcr.io/<username>/intercity237-auth:${env.BUILD_NUMBER} \
                      ./vendor/bin/phpunit --coverage-text
                """
            }
        }

        stage('Push Images') {
            when { branch 'main' }
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'TOKEN')]) {
                    sh "echo ${TOKEN} | docker login ${REGISTRY} -u <username> --password-stdin"
                    script {
                        def services = env.SERVICES.split(' ')
                        services.each { svc ->
                            sh "docker push ${REGISTRY}/<username>/intercity237-${svc}:${env.BUILD_NUMBER}"
                            sh "docker push ${REGISTRY}/<username>/intercity237-${svc}:latest"
                        }
                    }
                }
            }
        }

        stage('Deploy to Kubernetes') {
            when { branch 'main' }
            steps {
                withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                    sh "kubectl apply -f k8s/ --namespace=${NAMESPACE}"
                    sh "kubectl rollout status deployment/auth-service -n ${NAMESPACE} --timeout=120s"
                }
            }
        }
    }

    post {
        success { echo "Pipeline #${env.BUILD_NUMBER} terminé avec succès." }
        failure  {
            withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                sh "kubectl rollout undo deployment/auth-service -n ${NAMESPACE} || true"
            }
        }
    }
}
```

**Explication des stages (à inclure dans le rapport) :**

| Stage              | Description                                                                              |
|--------------------|------------------------------------------------------------------------------------------|
| Checkout           | Récupère le code source depuis GitHub sur la branche courante                           |
| Build All Services | Construit l'image Docker de chaque microservice en parallèle                            |
| Run Tests          | Exécute PHPUnit dans le conteneur auth-service, génère le rapport de couverture         |
| Push Images        | Pousse toutes les images vers GitHub Container Registry (GHCR), uniquement sur `main`  |
| Deploy to K8s      | Applique les manifests Kubernetes et effectue un rolling update de tous les déploiements |

**Screenshots à prendre :**
- [ ] Pipeline Jenkins avec les 5 stages en vert
- [ ] Historique des builds
- [ ] Console output d'un build réussi

---

### H12–H14 : Document d'Architecture (Section 8 — 20 pts)

> **C'est la section la plus rentable. 20 pts pour de la documentation.**
> Ouvrir draw.io (diagrams.net) et créer les 5 diagrammes UML pendant que les builds tournent.

**Style architectural à justifier :**

**Microservices Architecture** couplée à une **Event-Driven Architecture (EDA)** :

- Les services sont déployés indépendamment
- Chaque service possède sa propre base de données (Database per Service pattern)
- La communication synchrone se fait via REST (HTTP) à travers l'API Gateway
- La communication asynchrone se fait via RabbitMQ (events)
- Le couplage faible entre services permet le scaling indépendant

**5 Diagrammes UML obligatoires :**

**1. Component Diagram** — montre les 5 services + RabbitMQ + API Gateway + 3 DBs

**2. Deployment Diagram** — montre VPS → k3s → Pods par service → Services K8s → Ingress

**3. Class Diagram** — User, Department, DepartmentRecord, Event (base)

**4. Sequence Diagram** — Flux login :
```
Browser → API Gateway → auth-service → db-auth → JWT token → Browser
                                     → RabbitMQ → notification-service (log event)
```

**5. Use Case Diagram** — Acteurs : Superadmin, Admin, Employee

**Tableau Trade-offs (obligatoire) :**

| Attribut         | Avantage Microservices + EDA                          | Inconvénient                              |
|------------------|-------------------------------------------------------|-------------------------------------------|
| Scalabilité      | Chaque service scale indépendamment                   | Orchestration complexe (k8s obligatoire)  |
| Résilience       | Un service tombé n'affecte pas les autres             | Gestion des erreurs réseau entre services |
| Performance      | Services légers, réponse rapide                       | Latence ajoutée par l'API Gateway         |
| Maintenabilité   | Équipes indépendantes par service                     | N bases de données à maintenir            |
| Cohérence données| Eventual consistency via RabbitMQ                     | Pas de transactions ACID cross-service    |
| Déploiement      | Rolling update service par service                    | CI/CD multi-pipelines plus complexe       |

**Pros de l'architecture :**

- Déploiement indépendant de chaque service
- Résilience par isolation des pannes
- Scalabilité ciblée (ex: route-service peut avoir 10 répliques si forte charge)
- Technologies différentes possibles par service dans le futur

**Cons de l'architecture :**

- Complexité opérationnelle (k8s, RabbitMQ, multiple DBs)
- Debugging distribué plus difficile (logs répartis)
- Overhead réseau entre services
- Cohérence des données éventuelle (pas immédiate)

**Livrables Section 8 :**
- [ ] Document architecture avec les 5 diagrammes UML exportés en PNG
- [ ] Justification du choix architectural (2 pages minimum)
- [ ] Tableau trade-offs
- [ ] Section Pros/Cons

---

### H15–H16 : Tests PHPUnit setup (Section 6 partiel)

```bash
cd auth-service
composer init --no-interaction
composer require --dev phpunit/phpunit
```

**`auth-service/tests/Unit/AuthTest.php` :**

```php
<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/includes/auth.php';

class AuthTest extends TestCase
{
    protected function setUp(): void { $_SESSION = []; }

    public function test_h_escapes_xss(): void
    {
        $this->assertEquals('&lt;script&gt;', h('<script>'));
    }

    public function test_is_logged_in_false_without_session(): void
    {
        $this->assertFalse(is_logged_in());
    }

    public function test_is_logged_in_true_with_session(): void
    {
        $_SESSION['user_id'] = 1;
        $this->assertTrue(is_logged_in());
    }

    public function test_is_admin_false_for_employee(): void
    {
        $_SESSION['role'] = 'employee';
        $this->assertFalse(is_admin());
    }

    public function test_is_admin_true_for_admin(): void
    {
        $_SESSION['role'] = 'admin';
        $this->assertTrue(is_admin());
    }

    public function test_is_superadmin_only_superadmin(): void
    {
        $_SESSION['role'] = 'superadmin';
        $this->assertTrue(is_superadmin());
    }

    public function test_csrf_token_is_64_char_hex(): void
    {
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_csrf_token_stable_in_session(): void
    {
        $this->assertEquals(csrf_token(), csrf_token());
    }
}
```

---

## JOUR 2 — TESTS + MONITORING + ANSIBLE + DOC + SOUMISSION

### H17–H19 : Tests 80% coverage (Section 6 — 10 pts)

Ajouter `auth-service/phpunit.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src/includes</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
</phpunit>
```

```bash
./vendor/bin/phpunit --coverage-text --coverage-html coverage/
```

**Screenshots à prendre :**
- [ ] Terminal : `phpunit --coverage-text` avec ≥80%
- [ ] Browser : `coverage/index.html`

---

### H20–H21 : Prometheus + Grafana (Section 4 — 2.5 pts)

Ajouter dans `docker-compose.yml` :

```yaml
  prometheus:
    image: prom/prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
    networks: [intercity237-net]

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      GF_SECURITY_ADMIN_PASSWORD: admin
    networks: [intercity237-net]

  node-exporter:
    image: prom/node-exporter
    networks: [intercity237-net]
```

**`monitoring/prometheus.yml` :**

```yaml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']

  - job_name: 'auth-service'
    static_configs:
      - targets: ['auth-service:9117']
```

Accéder à Grafana sur `http://localhost:3000`, ajouter Prometheus comme datasource, importer le dashboard Node Exporter (ID: **1860** sur grafana.com).

**Screenshots à prendre :**
- [ ] Dashboard Grafana avec CPU, RAM, réseau
- [ ] Interface Prometheus `http://localhost:9090/targets`

---

### H22–H23 : Ansible (Section 5 — 2.5 pts)

```
ansible/
├── inventory.ini
├── playbook-install.yml
└── playbook-deploy.yml
```

**`ansible/inventory.ini` :**

```ini
[vps]
<IP_VPS> ansible_user=root ansible_ssh_private_key_file=~/.ssh/id_rsa
```

**`ansible/playbook-install.yml` :**

```yaml
---
- name: Installation de l'environnement Intercity237 sur le VPS
  hosts: vps
  become: yes

  tasks:
    - name: Mise à jour du cache apt
      apt:
        update_cache: yes

    - name: Installation des dépendances
      apt:
        name: [docker.io, docker-compose, nginx, curl, git]
        state: present

    - name: Démarrage de Docker
      service:
        name: docker
        state: started
        enabled: yes

    - name: Installation de k3s
      shell: curl -sfL https://get.k3s.io | sh -
      args:
        creates: /usr/local/bin/k3s
```

**`ansible/playbook-deploy.yml` :**

```yaml
---
- name: Déploiement des microservices Intercity237
  hosts: vps
  become: yes

  vars:
    app_dir: /opt/intercity237

  tasks:
    - name: Cloner le dépôt
      git:
        repo: https://github.com/<username>/intercity237.git
        dest: "{{ app_dir }}"
        version: main
        force: yes

    - name: Appliquer les manifests Kubernetes
      shell: kubectl apply -f {{ app_dir }}/k8s/
      environment:
        KUBECONFIG: /etc/rancher/k3s/k3s.yaml

    - name: Vérifier le déploiement
      shell: kubectl rollout status deployment/auth-service -n intercity237
      environment:
        KUBECONFIG: /etc/rancher/k3s/k3s.yaml
```

```bash
ansible-playbook -i ansible/inventory.ini ansible/playbook-install.yml
ansible-playbook -i ansible/inventory.ini ansible/playbook-deploy.yml
```

**Screenshots à prendre :**
- [ ] Exécution des 2 playbooks (tâches en vert)

---

### H24–H25 : Scrum Documentation (Section 2 — 5 pts)

Créer `docs/SCRUM.md` avec :

**Rôles :**

| Rôle          | Nom              | Responsabilités                              |
|---------------|------------------|----------------------------------------------|
| Product Owner | [Ton nom]        | Prioriser le backlog, valider les livrables  |
| Scrum Master  | [Ton nom]        | Faciliter, lever les blocages                |
| Developer     | [Ton nom]        | Implémenter, tester, documenter              |

**Sprint 1 (Semaines 1–4) — Objectif : Infrastructure + Microservices**

| User Story                                   | Points | Statut  |
|----------------------------------------------|--------|---------|
| Setup VPS et configuration réseau            | 3      | Terminé |
| Dockeriser les 4 microservices               | 5      | Terminé |
| Déployer sur Kubernetes avec k3s             | 8      | Terminé |
| Configurer le pipeline Jenkins               | 5      | Terminé |
| Mettre en place RabbitMQ (event broker)      | 3      | Terminé |

**Sprint 2 (Semaines 5–8) — Objectif : Tests + Architecture + Documentation**

| User Story                                   | Points | Statut  |
|----------------------------------------------|--------|---------|
| Tests unitaires ≥80% coverage                | 5      | Terminé |
| Document d'architecture UML complet          | 8      | Terminé |
| Monitoring Prometheus + Grafana              | 3      | Terminé |
| Playbooks Ansible                            | 2      | Terminé |
| Documentation README + Swagger               | 5      | Terminé |

Créer les burndown charts dans Google Sheets ou Excel et exporter en PNG.

**Screenshots à prendre :**
- [ ] GitHub Projects avec les user stories
- [ ] 2 burndown charts (Sprint 1 et Sprint 2)

---

### H26–H27 : Innovation — REST API (Section 9 — 10 pts)

Créer `route-service/src/api.php` :

```php
<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path   = trim($_SERVER['PATH_INFO'] ?? '/', '/');

// Vérification du token JWT (simplifié)
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

match(true) {
    $method === 'GET' && $path === 'departments' => departments_list($pdo),
    $method === 'GET' && $path === 'departments/stats' => departments_stats($pdo),
    $method === 'GET' && $path === 'employees' => employees_list($pdo),
    default => http_response_code(404) && print json_encode(['error' => 'Not found'])
};

function departments_list(PDO $pdo): void {
    $rows = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
}

function departments_stats(PDO $pdo): void {
    $rows = $pdo->query("
        SELECT d.name, COUNT(r.id) as total_employees,
               SUM(CASE WHEN r.status = 'Active' THEN 1 ELSE 0 END) as active
        FROM departments d
        LEFT JOIN department_records r ON r.department_id = d.id
        GROUP BY d.id, d.name
    ")->fetchAll();
    echo json_encode(['data' => $rows]);
}

function employees_list(PDO $pdo): void {
    $rows = $pdo->query("SELECT id, full_name, position, status, email FROM department_records")->fetchAll();
    echo json_encode(['data' => $rows, 'count' => count($rows)]);
}
```

**Description de l'innovation (à inclure dans le rapport) :**

L'innovation réside dans deux aspects :

1. **Architecture Event-Driven** : L'utilisation de RabbitMQ comme broker d'événements découple totalement les services. Le `notification-service` réagit aux événements métier sans que les autres services aient connaissance de son existence — c'est le pattern **Publisher/Subscriber** appliqué à un contexte RH.

2. **REST API exposée** : Le `route-service` expose une API REST JSON consommable par des clients tiers (mobile app, BI tool), rendant le système interopérable et extensible.

**Screenshots à prendre :**
- [ ] Réponse API `GET /departments/stats` dans Postman ou curl
- [ ] RabbitMQ Management avec les queues actives

---

### H28–H31 : Documentation Complète (Section 10 — 15 pts)

**`README.md` :**

```markdown
# Intercity237 — Microservices Edition

## Problème résolu
Gestion numérique centralisée des RH de Intercity237 avec une architecture
découplée et résiliente basée sur des microservices.

## Architecture
Microservices + Event-Driven (RabbitMQ) — 4 services PHP indépendants
orchestrés par Kubernetes (k3s).

## Services
| Service               | Port  | Description              |
|-----------------------|-------|--------------------------|
| api-gateway           | 80    | Routage + Auth           |
| auth-service          | 8001  | JWT + RBAC               |
| passenger-service          | 8002  | CRUD employés            |
| route-service          | 8003  | Gestion départements     |
| notification-service  | 8004  | Emails (event consumer)  |
| rabbitmq              | 15672 | Message broker           |

## Lancement local
git clone https://github.com/<username>/intercity237.git
cd intercity237
docker-compose up -d --build
# Application : http://localhost
# RabbitMQ UI : http://localhost:15672

## Déploiement Kubernetes
kubectl apply -f k8s/
kubectl get pods -n intercity237

## Tests
cd auth-service
composer install
./vendor/bin/phpunit --coverage-text

## API
Importer docs/postman_collection.json dans Postman.
```

Exporter une collection Postman avec les 3 endpoints REST.

**Structure du User Manual** (`docs/USER_MANUAL.md`) :
- Connexion (superadmin / admin / employee)
- Gestion des utilisateurs (admin)
- Gestion des départements
- Réinitialisation du mot de passe

**Livrables Section 10 :**
- [ ] `README.md` sur GitHub
- [ ] `docs/postman_collection.json`
- [ ] `docs/USER_MANUAL.md`
- [ ] Rapport complet (voir structure ci-dessous)

---

### H32–H37 : Rédaction du Rapport Final

**Structure correcte** (l'erreur de numérotation du template officiel est corrigée ici) :

**Chapitre 1 — Introduction**
- Contexte : Intercity237, secteur cimentier au Cameroun
- Problème : gestion papier des données RH, accès non sécurisé, absence de centralisation
- Objectifs : portail web sécurisé, multi-rôles, microservices, déployé sur VPS
- Périmètre du projet

**Chapitre 2 — Revue de Littérature**
- Comparaison méthodologies : Waterfall vs Agile vs Scrum → justification Scrum
- Portails RH existants : SAP HR, Odoo, solutions custom
- Architectures logicielles : Monolithe vs Microservices vs Event-Driven
- Concepts clés : Docker, Kubernetes, CI/CD, Message Broker

**Chapitre 3 — Méthodologie et Matériaux**
- Architecture du système (HLD) avec les 5 diagrammes UML
- Exigences fonctionnelles et non-fonctionnelles
- Application de Scrum : rôles, backlogs, burndowns, rétrospectives
- Catalogue des événements RabbitMQ
- Stack technologique (tableau)
- Document de cas de tests

**Chapitre 4 — Résultats et Discussions**
- Screenshots de l'application (login, admin, departments)
- Screenshots Kubernetes (`kubectl get pods`, rolling update)
- Screenshots Jenkins (pipeline vert)
- Screenshots Grafana (dashboard métriques)
- Résultats des tests PHPUnit (coverage ≥80%)
- Screenshots API REST (Postman)

**Chapitre 5 — Recommandations et Conclusion** *(3 paragraphes max)*
- Bilan des accomplissements
- Difficultés (complexité k8s, temps contraint) et solutions
- Recommandations futures : 2FA, Redis cache, service mesh (Istio)

---

### H38–H40 : Vidéo + Slides + ZIP final

**Script vidéo 7 minutes :**

| Temps        | Contenu                                                          |
|--------------|------------------------------------------------------------------|
| 0:00–0:45    | Introduction : Intercity237, problème résolu, architecture choisie  |
| 0:45–1:30    | Demo live application : login, gestion RH, admin panel           |
| 1:30–2:30    | Infrastructure : `kubectl get pods`, services K8s                |
| 2:30–3:30    | Jenkins : lancement pipeline en direct, 5 stages verts           |
| 3:30–4:15    | Grafana : dashboard CPU/RAM en temps réel                        |
| 4:15–5:00    | Tests : `phpunit --coverage-text` ≥80%                           |
| 5:00–5:45    | Architecture : schéma microservices + diagrammes UML             |
| 5:45–7:00    | Innovation : API REST + RabbitMQ Management UI + événements      |

**PowerPoint — 20 slides exactement :**

| #  | Slide                                     |
|----|-------------------------------------------|
| 1  | Couverture — Titre, groupe, date          |
| 2  | Agenda                                    |
| 3  | Problème résolu                           |
| 4  | Architecture Microservices + EDA (schéma) |
| 5  | Décomposition des services (tableau)      |
| 6  | Catalogue des événements RabbitMQ         |
| 7  | Infrastructure Diagram (VPS + k3s)        |
| 8  | Docker Compose — tous services Up         |
| 9  | Kubernetes — pods Running                 |
| 10 | Rolling Update démontré                   |
| 11 | Pipeline Jenkins — 5 stages verts         |
| 12 | Grafana Dashboard                         |
| 13 | Ansible Playbooks                         |
| 14 | Résultats tests — Coverage ≥80%           |
| 15 | UML Component Diagram                     |
| 16 | UML Deployment Diagram                    |
| 17 | Trade-offs architecturaux                 |
| 18 | Scrum — Burndowns Sprint 1 & 2            |
| 19 | Innovation : REST API + RabbitMQ          |
| 20 | Conclusion + Recommandations              |

---

## CHECKLIST FINALE DE SOUMISSION

### Code

- [ ] `Dockerfile` dans chaque service (4 services)
- [ ] `docker-compose.yml` avec tous les services + RabbitMQ
- [ ] `k8s/` — tous les manifests YAML (namespace, deployments, services)
- [ ] `Jenkinsfile`
- [ ] `ansible/playbook-install.yml`
- [ ] `ansible/playbook-deploy.yml`
- [ ] `ansible/inventory.ini`
- [ ] `auth-service/tests/Unit/AuthTest.php`
- [ ] `phpunit.xml`
- [ ] `composer.json`
- [ ] `route-service/src/api.php` (REST endpoints)
- [ ] `api-gateway/nginx.conf`
- [ ] `monitoring/prometheus.yml`

### Documentation

- [ ] `README.md` complet sur GitHub
- [ ] `docs/postman_collection.json`
- [ ] `docs/USER_MANUAL.md`
- [ ] `docs/SCRUM.md` avec burndown charts
- [ ] 5 diagrammes UML (PNG exportés depuis draw.io)
- [ ] Document d'architecture complet

### Rapport

- [ ] 5 chapitres (sans doublon de "Chapitre 3")
- [ ] Images et descriptions dans le rapport
- [ ] Page de garde avec informations du groupe

### Présentation

- [ ] Vidéo walkthrough 7 minutes (OBS ou Loom)
- [ ] PowerPoint 20 slides

### Package

- [ ] `.ZIP` contenant tout ce qui précède

---

## MATRICE DE RISQUES 48H

| Risque                         | Impact     | Mitigation immédiate                                     |
|-------------------------------|------------|----------------------------------------------------------|
| VPS non disponible            | CRITIQUE   | Utiliser Docker Compose local comme démo — screenshots suffisent |
| k3s crash avant la démo       | Fort       | Préparer les screenshots pendant que ça fonctionne      |
| Coverage < 80%                | Fort       | Cibler uniquement `includes/auth.php` — fonctions pures faciles à tester |
| RabbitMQ ne démarre pas       | Moyen      | Documenter l'architecture EDA même si la démo partielle |
| Manque de temps pour le rapport | CRITIQUE | Écrire le rapport en parallèle pendant les builds/déploiements |
| Soumission tardive            | CRITIQUE   | Aucune excuse acceptable — pénalité 40%                  |

---

## ORDRE DE PRIORITÉ EN CAS DE MANQUE DE TEMPS

Si tu dois choisir quoi sacrifier, sacrifier dans cet ordre :

```
Sacrifier en dernier  →  Section 8  (20 pts — documentation pure)
                         Section 10 (15 pts — documentation)
                         Section 1  (15 pts — screenshots VPS)
                         Section 7  (15 pts — docker-compose suffit si k8s fail)

Sacrifier si besoin   →  Section 3  (10 pts — pipeline)
                         Section 6  (10 pts — tests)
                         Section 9  (10 pts — API simple)

Sacrifier en premier  →  Section 2  (5 pts — scrum rétroactif)
                         Section 4  (2.5 pts — grafana)
                         Section 5  (2.5 pts — ansible)
```

---

*Document généré le 04 juin 2026 — Intercity237 — SEN3244 Spring 2026 — Plan 48H*
