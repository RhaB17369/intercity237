# Manuel Utilisateur — Intercity237
## Plateforme de réservation de bus interurbains — Cameroun

---

## Accès à la plateforme

**URL** : `http://<votre-domaine>` ou `http://localhost` (développement local)  
**Navigateurs supportés** : Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## Rôles et accès

| Rôle         | Accès                                                                          |
|--------------|--------------------------------------------------------------------------------|
| `passenger`  | Chercher un trajet, réserver, payer, voir ses tickets QR                       |
| `agent`      | Scanner les tickets QR à l'embarquement (`/scan.php`)                          |
| `admin`      | Voir les réservations, gérer les passagers, accéder au dashboard admin         |
| `superadmin` | Toutes les fonctionnalités + gestion des administrateurs                       |

**Compte superadmin par défaut** : `superadmin` / `Admin@1234` *(à changer après installation)*

---

## 1. Recherche d'un trajet

1. Naviguer vers la **page d'accueil** (`/`)
2. Choisir la **ville de départ** et la **ville d'arrivée**
3. Sélectionner la **date de voyage**
4. Cliquer **"Rechercher"**
5. Les résultats affichent les horaires disponibles avec :
   - Heure de départ / arrivée
   - Opérateur de bus et modèle du véhicule
   - Places disponibles
   - Prix en FCFA

---

## 2. Réservation et paiement

1. Cliquer **"Réserver"** sur le trajet choisi
2. Remplir le formulaire :
   - Nom complet du passager
   - Numéro de téléphone
   - Mode de paiement : **MTN Mobile Money** ou **Orange Money**
   - Numéro de téléphone Mobile Money
3. Cliquer **"Confirmer et payer"**

> **Note** : Le paiement réussit automatiquement pour tous les numéros **sauf** ceux commençant par `000`.

4. Redirection automatique vers votre **ticket QR**

---

## 3. Ticket QR

- Accessible via `/ticket/{référence}` (ex. `ICY-2026-A3B9C1`)
- Contient : trajet, date, heure de départ, opérateur, QR code
- **Présentez ce QR à l'agent lors de l'embarquement**

---

## 4. Scan du ticket à l'embarquement (Agents)

1. Naviguer vers `/scan.php`
2. Saisir le token QR ou scanner avec un lecteur
3. Résultats possibles :
   - ✅ **VALIDE** — passager autorisé, ticket marqué comme utilisé
   - ❌ **DÉJÀ SCANNÉ** — ticket déjà utilisé, refuser
   - ❌ **INVALIDE** — token inconnu, refuser

---

## 5. Dashboard Admin (`/admin/`)

Accessible aux rôles `admin` et `superadmin` :

| Page | URL | Description |
|------|-----|-------------|
| Dashboard | `/admin/` | Stats temps réel : réservations, revenus, départs du jour |
| Passagers | `/admin/users.php` | Liste des voyageurs avec nombre de réservations |
| Admins | `/admin/admins.php` | Gestion des comptes admin (superadmin uniquement) |
| API Routes | `/api/routes` | Toutes les lignes JSON |
| Scanner | `/scan.php` | Validation des tickets |

---

## 6. REST API (route-service)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/health` | Statut du service |
| `GET` | `/api/cities` | 10 villes desservies |
| `GET` | `/api/routes` | Lignes avec tarifs et durées |
| `GET` | `/api/schedules` | Prochains départs |
| `GET` | `/api/schedules?origin=Douala&destination=Yaoundé&date=YYYY-MM-DD` | Recherche filtrée |
| `GET` | `/api/schedules/{id}` | Détail d'un horaire |

Voir `docs/openapi.yaml` pour la spécification Swagger complète.

---

## 7. Inscription d'un passager

1. Aller sur `/register.php`
2. Remplir : Nom complet, Email, Téléphone, Nom d'utilisateur, Mot de passe
3. Mot de passe requis : ≥8 caractères, majuscule, minuscule, chiffre
4. Cliquer **"Créer mon compte"** — connexion automatique

---

## 8. Installation rapide (développeurs)

```bash
git clone https://github.com/RhaB17369/intercity237.git && cd intercity237
docker-compose up -d --build
# Application : http://localhost
# Prometheus  : http://localhost:9090
# Grafana     : http://localhost:3000  (admin / admin123)
# RabbitMQ UI : http://localhost:15672 (guest / guest)
```

---

*SEN3244 Software Architecture — Engr. TEKOH PALMA — The ICT University — Spring 2026*
