# Manuel Utilisateur — Intercity237
## Guide d'utilisation par rôle

---

## Accès au portail

**URL** : http://\<votre-domaine\>  
**Navigateurs supportés** : Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## Rôles disponibles

| Rôle       | Accès                                                             |
|------------|-------------------------------------------------------------------|
| Employee   | Consulter ses informations RH, voir son département              |
| Admin      | Gérer les employés, voir tous les départements, accès admin       |
| Superadmin | Toutes les fonctionnalités + gestion des administrateurs          |

**Compte superadmin par défaut** : `superadmin` / `Admin@1234` (à changer après installation)

---

## 1. Connexion

1. Naviguer vers la page d'accueil
2. Cliquer sur **Login** (coin supérieur droit)
3. Saisir le nom d'utilisateur et le mot de passe
4. Cliquer sur **Sign In**

En cas d'oubli du mot de passe : cliquer sur **Forgot Password** et suivre les instructions par email.

---

## 2. Tableau de bord (Home)

Après connexion, la page d'accueil affiche :
- Nombre total d'employés
- Nombre d'administrateurs
- Nombre de départements
- Nombre de records RH

---

## 3. Gestion des départements (Employee + Admin)

1. Dans la navbar, cliquer sur **Departments**
2. Sélectionner un département dans la liste déroulante
3. La page du département affiche tous les records des employés

**Note** : Les employés ne voient que leur propre département. Les admins voient tous les départements.

---

## 4. Panel Admin

Accessible uniquement aux rôles `admin` et `superadmin`.

### 4.1 Dashboard Admin
- Vue globale des statistiques
- Liste des utilisateurs récemment inscrits

### 4.2 Gérer les Employés (Manage Employees)
1. Cliquer sur **Admin → Manage Employees**
2. Voir la liste complète de tous les utilisateurs
3. **Créer** un utilisateur : cliquer sur **+ Add User**
4. **Modifier** un utilisateur : cliquer sur l'icône crayon
5. **Supprimer** un utilisateur : cliquer sur l'icône corbeille (confirmation requise)

### 4.3 Database View
Vue tabulaire de tous les records RH classés par département.

---

## 5. Gestion des Admins (Superadmin uniquement)

1. Cliquer sur **Admin → Manage Admins**
2. Promouvoir un employé en admin : sélectionner et changer le rôle
3. Révoquer les droits admin : ramener le rôle à `employee`

---

## 6. Inscription (Register)

1. Cliquer sur **Register** depuis la page d'accueil
2. Remplir le formulaire : nom complet, email, username, département, mot de passe
3. Le mot de passe doit contenir minimum 8 caractères, une majuscule et un chiffre
4. Cliquer sur **Create Account**
5. Se connecter avec les identifiants créés

---

## 7. API REST (Intégration technique)

Le `route-service` expose une API REST JSON.

**Base URL** : `http://<domaine>/api.php`

| Méthode | Endpoint                   | Description                        | Auth |
|---------|----------------------------|------------------------------------|------|
| GET     | `/api.php/health`          | Santé du service                   | Non  |
| GET     | `/api.php/departments`     | Liste des départements             | Non  |
| GET     | `/api.php/departments/stats` | Statistiques par département     | Non  |
| GET     | `/api.php/employees`       | Liste des employés (max 100)       | Non  |
| POST    | `/api.php/employees`       | Créer un record employé            | Non  |

**Exemple de requête** :
```bash
curl http://<domaine>/api.php/departments/stats
```

**Exemple de réponse** :
```json
{
  "service": "route-service",
  "total_employees": 42,
  "departments": 10,
  "data": [
    {
      "id": "1",
      "name": "Production",
      "total_records": "12",
      "active": "10",
      "on_leave": "1",
      "suspended": "0",
      "resigned": "1"
    }
  ]
}
```

---

## 8. Résolution des problèmes courants

| Problème                        | Solution                                                      |
|---------------------------------|---------------------------------------------------------------|
| "Database unavailable"          | Vérifier que le conteneur MariaDB est en état `Up`            |
| Page blanche après login        | Effacer les cookies et sessions du navigateur                 |
| "Access denied"                 | Votre rôle ne permet pas cette action — contacter un admin    |
| Mot de passe oublié             | Utiliser Forgot Password depuis la page de connexion          |
| Application inaccessible        | Vérifier `docker-compose ps` ou `kubectl get pods -n intercity237`|
