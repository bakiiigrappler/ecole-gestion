# 🧪 Guide de Test - Logo et Informations sur le Bulletin

## ✅ État Actuel

Les modifications ont été effectuées avec succès :
- ✅ L'API envoie les informations de l'école
- ✅ Le système charge le logo dynamiquement
- ✅ Les paramètres de l'école sont configurés
- ✅ Le PDF affiche les informations de l'école

## 🧪 Comment Tester

### Étape 1 : Vérifier les Paramètres de l'École

Exécutez cette commande pour vérifier la configuration :
```bash
php check_school_settings.php
```

**Résultat attendu :**
```
✅ Paramètres de l'école trouvés !

📋 INFORMATIONS DE L'ÉTABLISSEMENT
Nom de l'école (Préprimaire/Primaire)  : ✅ ÉCOLE PRIVÉE
Téléphone                               : ✅ 06037499
Ville                                   : ✅ Libreville
Devise de l'école                       : ✅ Travail - Rigueur - Discipline
Année scolaire                          : ✅ 2024-2025
```

### Étape 2 : Tester l'API Directement

Exécutez cette commande :
```bash
php test_api_school_settings.php
```

Cela vous donnera l'URL pour tester l'API dans le navigateur.

Ou visitez directement :
```
http://127.0.0.1:8000/pre-primary-evaluations/api/student-data/1
```

**Vérifiez que la réponse JSON contient :**
```json
{
  "success": true,
  "schoolSettings": {
    "primary_school_name": "ÉCOLE PRIVÉE",
    "school_phone": "06037499",
    "school_address": "",
    "school_bp": "BP: 6",
    "city": "Libreville",
    "school_motto": "Travail - Rigueur - Discipline",
    "school_logo": null
  }
}
```

### Étape 3 : Tester la Génération du Bulletin

1. **Accédez à l'application** :
   ```
   http://127.0.0.1:8000
   ```

2. **Connectez-vous** avec vos identifiants

3. **Allez dans** : `Évaluations → Notes Préprimaire`

4. **Sélectionnez une classe** avec des évaluations

5. **Cliquez sur "Voir Bulletin"** pour un élève

6. **Ouvrez la Console du Navigateur** (F12)
   - Onglet "Console"

7. **Cliquez sur "Télécharger PDF"**

8. **Vérifiez les logs dans la console** :
   ```javascript
   Données reçues de l'API: {success: true, studentData: {...}, schoolSettings: {...}}
   Paramètres de l'école: {primary_school_name: "ÉCOLE PRIVÉE", school_phone: "06037499", ...}
   URL du logo: null
   ```

9. **Vérifiez le PDF téléchargé** :
   - Page 1 : Devrait afficher
     - ✅ "Ministère de l'Education Nationale"
     - ✅ "ÉCOLE PRIVÉE" (nom de l'école)
     - ✅ "Tél : 06037499"
     - ✅ "Libreville"
     - ✅ Initiales "EP" dans un cadre bleu (logo placeholder)
     - ✅ "Travail - Rigueur - Discipline"
     - ✅ Informations de l'élève

## 📝 Configuration du Logo

### Pour Ajouter un Logo à Votre École

1. **Accédez aux paramètres** :
   ```
   http://127.0.0.1:8000/admin/school-settings
   ```

2. **Dans la section "Logo de l'école"** :
   - Cliquez sur "Choisir un fichier"
   - Sélectionnez votre logo (PNG, JPEG, max 2 Mo)
   - Format recommandé : 500x500 pixels, fond transparent

3. **Cliquez sur "Enregistrer"**

4. **Vérifiez que le logo est accessible** :
   ```
   http://127.0.0.1:8000/storage/school/[nom-du-fichier]
   ```

5. **Régénérez un bulletin** et vérifiez que le logo s'affiche

### Si le Logo Ne S'Affiche Pas

**Vérifications :**

1. **Lien symbolique** :
   ```bash
   php artisan storage:link
   ```

2. **Permissions** :
   Assurez-vous que le dossier `storage/app/public/school` existe et est accessible

3. **Cache** :
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

4. **Console du navigateur** :
   - Ouvrez F12
   - Cherchez les erreurs liées au chargement d'image
   - Vérifiez l'URL du logo dans les logs

## 🎨 Personnalisation

### Modifier les Informations Affichées

Éditez dans l'interface d'administration :
```
http://127.0.0.1:8000/admin/school-settings
```

Les champs suivants apparaissent sur le bulletin :
- **Nom de l'école primaire** → En-tête du bulletin (gros titre)
- **Téléphone** → "Tél : ..."
- **Ville** → Sous le téléphone
- **Devise** → Sous le logo
- **Logo** → Centre de la page

### Exemple de Configuration

```
Nom de l'école primaire : ÉCOLE PRIVÉE BLESSING SCHOOL
Téléphone              : 066 61 66 10 / 066 38 42 50
Ville                  : Okala CICIBA
Devise                 : Travail - Rigueur - Discipline
Logo                   : [Télécharger votre logo]
```

## 🐛 Dépannage

### Problème : "Les informations de l'école ne s'affichent pas"

**Solution 1** : Vérifier les paramètres
```bash
php check_school_settings.php
```

**Solution 2** : Réexécuter le seeder
```bash
php artisan db:seed --class=SchoolSettingsSeeder
```

**Solution 3** : Nettoyer les caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Problème : "Le logo ne se charge pas"

**Symptôme dans la console** :
```
Failed to load image
```

**Solutions** :

1. **Vérifier le lien symbolique** :
   ```bash
   php artisan storage:link
   ```

2. **Vérifier que le fichier existe** :
   ```bash
   php check_school_settings.php
   ```
   
3. **Tester l'URL directement** :
   ```
   http://127.0.0.1:8000/storage/school/[nom-fichier]
   ```

4. **Vérifier les permissions** :
   - Windows : Clic droit sur `storage/app/public` → Propriétés → Sécurité
   - Linux/Mac : `chmod -R 775 storage`

### Problème : "schoolSettings est undefined"

**Vérification** :
1. Ouvrez la console du navigateur (F12)
2. Tapez : `console.log(data.schoolSettings)`
3. Si undefined, vérifiez l'API :
   ```
   http://127.0.0.1:8000/pre-primary-evaluations/api/student-data/1
   ```

**Solution** : Redémarrez le serveur
```bash
php artisan serve
```

## ✅ Checklist de Test

Avant de valider que tout fonctionne :

- [ ] Les paramètres de l'école sont configurés (`php check_school_settings.php`)
- [ ] L'API renvoie les informations (`php test_api_school_settings.php`)
- [ ] La console du navigateur affiche les données correctement
- [ ] Le PDF généré contient le nom de l'école
- [ ] Le PDF généré contient le téléphone
- [ ] Le PDF généré contient la ville
- [ ] Le PDF généré contient la devise
- [ ] Le logo s'affiche (si configuré) OU les initiales s'affichent
- [ ] Toutes les informations sont lisibles et bien formatées

## 📞 Support

Pour toute question, consultez :
- `GUIDE_CONFIGURATION_LOGO_ECOLE.md` - Configuration détaillée
- `DOCUMENTATION_EGESCO.txt` - Documentation complète

---

**Date de création** : Novembre 2024  
**Version** : 1.0  
**Système** : EGESCO - Gestion Scolaire

