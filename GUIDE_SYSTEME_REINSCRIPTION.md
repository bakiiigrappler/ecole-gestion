# 📚 Guide du Système de Réinscription Automatique

## 🎯 Vue d'ensemble

Le système de réinscription automatique permet de déterminer intelligemment le statut d'un élève lors de son inscription :
- **Nouveau** : Première inscription ou inscription dans une classe différente
- **Redoublant** : Échec l'année précédente (moyenne < 10/20)
- **Passant** : Admis et passage en classe supérieure (moyenne >= 10/20)

---

## 🔧 Fonctionnalités

### 1. **Détection automatique du statut**
Le système analyse automatiquement :
- L'historique des inscriptions de l'élève
- Les notes de l'année précédente
- La moyenne générale calculée avec coefficients
- La progression de classe (classe suivante ou redoublement)

### 2. **Workflow d'inscription**

#### **Nouvelle inscription (premier élève)**
1. Cocher "Nouvel élève (première inscription)"
2. Remplir les informations de l'élève
3. Statut automatique : **NOUVEAU**

#### **Réinscription (élève existant)**
1. Décocher "Nouvel élève (première inscription)"
2. Entrer le matricule de l'élève (ex: STU2024TEST001)
3. Sélectionner la classe d'inscription
4. Cliquer sur "Vérifier"
5. Le système affiche :
   - Statut détecté (Nouveau/Redoublant/Passant)
   - Informations de l'élève
   - Classe précédente
   - Moyenne de l'année précédente
   - Commentaires explicatifs

---

## 📊 Cas d'usage et exemples

### **CAS 1: Élève PASSANT** ✅
- **Matricule** : STU2024TEST001
- **Nom** : Jean PASSANT
- **Classe précédente** : CP A
- **Moyenne** : ~15/20
- **Résultat** : Admis
- **Statut** : **PASSANT** (passage en CE1 A)
- **Commentaire** : "Admis avec moyenne de 15/20 - Passage en classe supérieure"

### **CAS 2: Élève REDOUBLANT** ⚠️
- **Matricule** : STU2024TEST002
- **Nom** : Marie REDOUBLANT
- **Classe précédente** : CP A
- **Moyenne** : ~7/20
- **Résultat** : Échec
- **Statut** : **REDOUBLANT** (redouble en CP A)
- **Commentaire** : "Moyenne insuffisante (7/20) - Redoublement"

### **CAS 3: NOUVEL ÉLÈVE** 🆕
- **Matricule** : STU2024TEST003
- **Nom** : Pierre NOUVEAU
- **Inscription** : Première fois
- **Statut** : **NOUVEAU**
- **Commentaire** : "Aucune inscription précédente trouvée"

### **CAS 4: Élève admis mais classe différente** 🔄
- **Matricule** : STU2024TEST004
- **Nom** : Sophie DIFFERENT
- **Classe précédente** : CE1 A
- **Moyenne** : ~13/20
- **Résultat** : Admis
- **Si inscrit en CP** : **NOUVEAU** (classe différente)
- **Commentaire** : "Admis mais inscription dans une classe différente"

### **CAS 5: Élève avec moyenne limite** ⚖️
- **Matricule** : STU2024TEST005
- **Nom** : Lucas LIMITE
- **Classe précédente** : CP A
- **Moyenne** : ~10/20
- **Résultat** : Juste admis
- **Statut** : **PASSANT** (passage en CE1 A)
- **Commentaire** : "Admis avec moyenne de 10/20 - Passage en classe supérieure"

---

## 🗄️ Structure de la base de données

### **Nouveaux champs dans `enrollments`**

| Champ | Type | Description |
|-------|------|-------------|
| `is_reinscription` | boolean | Indique si c'est une réinscription |
| `reinscription_student_id` | string | Matricule de l'élève pour réinscription |
| `student_status` | enum | Statut: nouveau/redoublant/passant |
| `previous_class_id` | bigint | ID de la classe précédente |
| `previous_academic_year_id` | bigint | ID de l'année scolaire précédente |
| `previous_year_result` | enum | Résultat: admis/redouble/non_applicable |
| `previous_year_average` | decimal | Moyenne générale de l'année précédente |
| `status_comments` | text | Commentaires sur le statut |

---

## 🔍 Logique de détermination du statut

### **Algorithme**

```
SI is_reinscription = false OU reinscription_student_id vide
    ALORS statut = "nouveau"
    
SINON
    Trouver élève par matricule
    
    SI élève non trouvé
        ALORS statut = "nouveau"
        
    SINON
        Trouver dernière inscription
        Calculer moyenne année précédente
        
        SI moyenne >= 10
            ALORS résultat = "admis"
            
            SI classe actuelle = classe suivante
                ALORS statut = "passant"
            SINON
                statut = "nouveau"
        SINON
            résultat = "redouble"
            statut = "redoublant"
```

### **Calcul de la moyenne**

La moyenne est calculée avec les coefficients des matières :
```
Moyenne = Σ(note × coefficient) / Σ(coefficients)
```

### **Détection de la classe suivante**

Le système vérifie :
1. Même cycle (préprimaire, primaire, collège, lycée)
2. Ordre du niveau supérieur de 1 (ex: CP ordre 1 → CE1 ordre 2)

---

## 🚀 Utilisation

### **1. Accéder au formulaire d'inscription**
```
URL: /enrollments/create
```

### **2. Pour un nouvel élève**
- Laisser coché "Nouvel élève (première inscription)"
- Remplir les informations
- Le statut sera automatiquement "nouveau"

### **3. Pour une réinscription**
1. Décocher "Nouvel élève (première inscription)"
2. Les champs de réinscription apparaissent
3. Entrer le matricule de l'élève
4. Sélectionner la classe d'inscription
5. Cliquer sur "Vérifier"
6. Le système affiche le statut détecté
7. Continuer avec l'inscription

---

## 📡 API

### **Endpoint de vérification du statut**

**POST** `/api/enrollments/check-student-status`

**Paramètres:**
```json
{
    "student_id": "STU2024TEST001",
    "class_id": 1,
    "academic_year_id": 2
}
```

**Réponse (succès):**
```json
{
    "success": true,
    "student_status": "passant",
    "previous_class_id": 1,
    "previous_academic_year_id": 1,
    "previous_year_result": "admis",
    "previous_year_average": 15.50,
    "status_comments": "Admis avec moyenne de 15.50/20 - Passage en classe supérieure",
    "student_info": {
        "first_name": "Jean",
        "last_name": "PASSANT",
        "student_id": "STU2024TEST001"
    },
    "previous_class": "CP A",
    "current_class": "CE1 A"
}
```

---

## 🧪 Tests

### **Exécuter le seeder de test**
```bash
php artisan db:seed --class=EnrollmentStatusTestSeeder
```

### **Matricules de test disponibles**

#### **🎒 PRIMAIRE**

| Matricule | Nom | Cas | Statut attendu |
|-----------|-----|-----|----------------|
| STU2024TEST001 | Jean PASSANT | Admis, bonne moyenne | PASSANT (CP → CE1) |
| STU2024TEST002 | Marie REDOUBLANT | Échec, mauvaise moyenne | REDOUBLANT (CP → CP) |
| STU2024TEST003 | Pierre NOUVEAU | Première inscription | NOUVEAU |
| STU2024TEST004 | Sophie DIFFERENT | Admis, classe différente | NOUVEAU (CE1 → CP) |
| STU2024TEST005 | Lucas LIMITE | Admis, moyenne limite | PASSANT (CP → CE1) |

#### **🎓 COLLÈGE**

| Matricule | Nom | Cas | Statut attendu |
|-----------|-----|-----|----------------|
| STU2024TEST006 | Aminata COLLEGE | Admis, bonne moyenne | PASSANT (6ème → 5ème) |
| STU2024TEST007 | Ibrahim COLLEGEREDOUBLE | Échec, mauvaise moyenne | REDOUBLANT (6ème → 6ème) |

#### **🎓 LYCÉE**

| Matricule | Nom | Cas | Statut attendu |
|-----------|-----|-----|----------------|
| STU2024TEST008 | Fatima LYCEE | Admis, bonne moyenne | PASSANT (Seconde → Première) |
| STU2024TEST009 | Olivier LYCEEREDOUBLE | Échec, mauvaise moyenne | REDOUBLANT (Seconde → Seconde) |

---

## 💡 Règles métier

### **Règle 1: Seuil de passage**
- Moyenne >= 10/20 : Admis
- Moyenne < 10/20 : Redouble

### **Règle 2: Progression de classe**
- Si admis ET classe suivante : **PASSANT**
- Si admis ET classe différente : **NOUVEAU**
- Si échec : **REDOUBLANT**

### **Règle 3: Première inscription**
- Aucune inscription précédente : **NOUVEAU**
- Matricule non trouvé : **NOUVEAU**

---

## 🎨 Interface utilisateur

### **Badges de statut**

- 🟢 **NOUVEAU** : Badge vert
- 🟡 **REDOUBLANT** : Badge jaune/warning
- 🔵 **PASSANT** : Badge bleu/primary

### **Affichage des informations**

Lorsqu'un statut est détecté, le système affiche :
- Icône selon le statut
- Badge coloré
- Commentaires explicatifs
- Informations de l'élève
- Classe précédente et moyenne
- Classe d'inscription actuelle

---

## 📝 Notes importantes

1. **Calcul de moyenne** : Utilise les coefficients des matières
2. **Année scolaire** : Compare avec l'année précédente
3. **Progression** : Vérifie l'ordre des niveaux dans le même cycle
4. **Flexibilité** : Permet l'inscription dans n'importe quelle classe
5. **Traçabilité** : Tous les commentaires sont sauvegardés

---

## 🔐 Sécurité

- Validation des données côté serveur
- Vérification de l'existence des classes et années scolaires
- Gestion des erreurs avec messages explicites
- Protection CSRF sur toutes les requêtes

---

## 🛠️ Maintenance

### **Modifier le seuil de passage**
Dans `EnrollmentController.php` et `Enrollment.php`, ligne :
```php
$hasPassed = $previousAverage >= 10; // Modifier le seuil ici
```

### **Ajouter des critères supplémentaires**
Modifier la méthode `determineStudentStatus()` dans le modèle `Enrollment.php`

---

## 📞 Support

Pour toute question ou problème, consultez :
- Les logs Laravel : `storage/logs/laravel.log`
- La console du navigateur pour les erreurs JavaScript
- Les réponses API pour le débogage

---

## ✅ Checklist de déploiement

- [x] Migration exécutée
- [x] Modèle Enrollment mis à jour
- [x] Formulaire d'inscription modifié
- [x] API de vérification créée
- [x] Données de test insérées
- [x] Documentation créée

---

**Date de création** : 8 octobre 2025
**Version** : 1.0
**Auteur** : Système de gestion École
