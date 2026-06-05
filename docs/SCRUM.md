# Application de Scrum — Intercity237
## SEN3244 Software Architecture — Spring 2026

---

## Rôles Scrum

| Rôle          | Membre              | Responsabilités                                                    |
|---------------|---------------------|--------------------------------------------------------------------|
| Product Owner | Yves Lepa           | Définir et prioriser le backlog, valider les livrables             |
| Scrum Master  | Yves Lepa           | Faciliter les cérémonies Scrum, lever les blocages                 |
| Developer     | Yves Lepa           | Implémenter les fonctionnalités, écrire les tests, documenter      |

---

## Definition of Done (DoD)

Une User Story est considérée terminée quand :
- Le code est implémenté et fonctionnel
- Les tests unitaires sont écrits (couverture ≥80%)
- Le Dockerfile est mis à jour si nécessaire
- La documentation est mise à jour
- Le code est mergé dans `main` via Pull Request

---

## Product Backlog

| ID   | User Story                                                         | Points | Priorité | Sprint |
|------|---------------------------------------------------------------------|--------|----------|--------|
| US01 | Setup VPS et configuration de l'environnement serveur              | 3      | Haute    | 1      |
| US02 | Dockeriser les 4 microservices PHP                                  | 5      | Haute    | 1      |
| US03 | Mettre en place l'API Gateway Nginx                                 | 3      | Haute    | 1      |
| US04 | Configurer RabbitMQ comme message broker                            | 3      | Haute    | 1      |
| US05 | Déployer sur Kubernetes avec k3s                                    | 8      | Haute    | 1      |
| US06 | Mettre en place le pipeline CI/CD Jenkins                           | 8      | Haute    | 1      |
| US07 | Écrire les tests unitaires (≥80% couverture)                        | 5      | Haute    | 2      |
| US08 | Documenter l'architecture Microservices + EDA (UML)                 | 8      | Haute    | 2      |
| US09 | Configurer le monitoring Prometheus + Grafana                       | 3      | Moyenne  | 2      |
| US10 | Écrire les playbooks Ansible                                        | 2      | Moyenne  | 2      |
| US11 | Implémenter la REST API routes/schedules/cities (route-service)     | 5      | Moyenne  | 2      |
| US12 | Rédiger la documentation complète (README, User Manual)             | 5      | Moyenne  | 2      |

**Total Story Points** : 58

---

## Sprint 1 — Infrastructure et Conteneurisation
**Durée** : Semaines 1–4  
**Objectif** : Avoir une infrastructure opérationnelle avec tous les services déployés

### Sprint Backlog

| ID   | Tâche                                          | Assigné | Statut  | Points |
|------|------------------------------------------------|---------|---------|--------|
| US01 | Provisionner le VPS (Hetzner/Oracle)           | Dev     | Terminé | 3      |
| US01 | Configurer Nginx reverse proxy                 | Dev     | Terminé | -      |
| US02 | Créer Dockerfile auth-service                  | Dev     | Terminé | 5      |
| US02 | Créer Dockerfile passenger-service                  | Dev     | Terminé | -      |
| US02 | Créer Dockerfile route-service                  | Dev     | Terminé | -      |
| US02 | Créer Dockerfile notification-service          | Dev     | Terminé | -      |
| US03 | Créer api-gateway/nginx.conf                   | Dev     | Terminé | 3      |
| US04 | Configurer RabbitMQ dans docker-compose        | Dev     | Terminé | 3      |
| US04 | Créer notification-service consumer            | Dev     | Terminé | -      |
| US05 | Créer tous les manifests Kubernetes (k8s/)     | Dev     | Terminé | 8      |
| US06 | Rédiger le Jenkinsfile multi-services          | Dev     | Terminé | 8      |

**Total Sprint 1** : 30 points  
**Vélocité** : 30 pts / 4 semaines

### Burndown Chart Sprint 1

```
Points restants
30 |  *
25 |     *
20 |        *
15 |           *
10 |              *
 5 |                 *
 0 |                    *
   +---+---+---+---+---+---+---+
   S1  J3  J5  J7  J10 J12 J14 J15
                                Jours
```

### Rétrospective Sprint 1

**Ce qui a bien fonctionné :**
- La conteneurisation avec Docker s'est faite rapidement grâce à la base PHP existante
- k3s s'est avéré plus simple qu'un cluster Kubernetes complet

**Ce qui a été difficile :**
- La configuration du healthcheck MariaDB a demandé plusieurs itérations
- La synchronisation des services au démarrage (dépendances de santé RabbitMQ)

**Actions d'amélioration pour Sprint 2 :**
- Démarrer la documentation en parallèle du développement
- Écrire les tests unitaires dès le début de chaque feature

---

## Sprint 2 — Tests, Architecture et Documentation
**Durée** : Semaines 5–8  
**Objectif** : Atteindre ≥80% coverage, documenter l'architecture, finaliser pour la soumission

### Sprint Backlog

| ID   | Tâche                                             | Assigné | Statut  | Points |
|------|---------------------------------------------------|---------|---------|--------|
| US07 | Configurer PHPUnit + phpunit.xml                  | Dev     | Terminé | 5      |
| US07 | Rédiger AuthTest.php (26 tests)                   | Dev     | Terminé | -      |
| US08 | Créer les 5 diagrammes UML                        | Dev     | Terminé | 8      |
| US08 | Rédiger le document d'architecture (trade-offs)   | Dev     | Terminé | -      |
| US09 | Configurer Prometheus + Grafana                   | Dev     | Terminé | 3      |
| US10 | Écrire playbook-install.yml                       | Dev     | Terminé | 2      |
| US10 | Écrire playbook-deploy.yml                        | Dev     | Terminé | -      |
| US11 | Implémenter api.php (REST endpoints)              | Dev     | Terminé | 5      |
| US12 | Rédiger README.md complet                         | Dev     | Terminé | 5      |
| US12 | Rédiger User Manual                               | Dev     | Terminé | -      |

**Total Sprint 2** : 28 points  
**Vélocité** : 28 pts / 4 semaines

### Burndown Chart Sprint 2

```
Points restants
28 |  *
24 |     *
20 |        *
16 |           *
12 |              *
 8 |                 *
 4 |                    *
 0 |                       *
   +---+---+---+---+---+---+---+
   S1  J3  J5  J7  J10 J12 J14 J15
                                Jours
```

### Rétrospective Sprint 2

**Ce qui a bien fonctionné :**
- Les tests unitaires sur `auth.php` ont été faciles à écrire (fonctions pures)
- La REST API a été implémentée rapidement grâce à PDO déjà en place

**Ce qui a été difficile :**
- Atteindre 80% de coverage sur du code PHP qui utilise `header()` et `die()`
- La rédaction des diagrammes UML a pris plus de temps que prévu

**Actions pour la prochaine itération :**
- Ajouter Redis pour le cache des sessions (scalabilité)
- Implémenter la 2FA pour renforcer la sécurité

---

## Outils Scrum utilisés

- **GitHub Projects** : Tableau Kanban (Backlog / In Progress / Done)
- **GitHub Issues** : User Stories et bugs
- **GitHub Pull Requests** : Code review et merge
- **Google Sheets** : Burndown charts
