# 📚 Guide de Configuration du Logo et Informations de l'École

## 🎯 Objectif
Ce guide vous explique comment configurer le logo et les informations de votre établissement qui apparaîtront sur les bulletins scolaires du préprimaire.

## 🔧 Étapes de Configuration

### 1. **Accéder à la Page de Paramètres**

Connectez-vous en tant qu'administrateur et accédez à :
```
Menu Admin → Paramètres de l'Établissement
```

Ou directement via l'URL :
```
http://127.0.0.1:8000/admin/school-settings
```

### 2. **Remplir les Informations Obligatoires**

#### **Informations Générales**
- **Année scolaire** : Format `2024-2025` (obligatoire)
- **Nom de l'école primaire** : Le nom qui apparaîtra sur les bulletins du préprimaire et primaire
  - Exemple : "ÉCOLE PRIVÉE BLESSING SCHOOL"
- **Nom de l'école secondaire** : Le nom pour le collège et lycée
  - Exemple : "LYCÉE BLESSING SCHOOL"

#### **Coordonnées**
- **Téléphone(s)** : Les numéros de téléphone de l'établissement
  - Exemple : "066 61 66 10 / 066 38 42 50"
- **Ville** : La ville où se trouve l'établissement
  - Exemple : "Okala CICIBA"
- **Adresse** : L'adresse complète (optionnel)
- **BP** : Boîte postale (optionnel)

#### **Identité de l'École**
- **Devise** : La devise de l'école
  - Exemple : "Travail - Rigueur - Discipline"
- **Description** : Une description de l'établissement (optionnel)

### 3. **Télécharger le Logo**

#### **Format du Logo**
- **Formats acceptés** : JPEG, PNG, JPG, GIF
- **Taille maximale** : 2 Mo
- **Recommandation** : 
  - Résolution : 500x500 pixels minimum
  - Format carré de préférence
  - Fond transparent (PNG recommandé)

#### **Procédure**
1. Cliquez sur "Choisir un fichier" dans la section "Logo de l'école"
2. Sélectionnez votre logo depuis votre ordinateur
3. Vérifiez que l'aperçu s'affiche correctement

### 4. **Enregistrer les Paramètres**

Cliquez sur le bouton **"Enregistrer les paramètres"** en bas du formulaire.

Un message de confirmation devrait apparaître : 
```
✅ Paramètres de l'établissement mis à jour avec succès.
```

## 📄 Résultat sur le Bulletin

Une fois configuré, le bulletin PDF du préprimaire affichera :

### **Page de Couverture**
```
┌─────────────────────────────────────────┐
│   Ministère de l'Education Nationale    │
│                                          │
│        ÉCOLE PRIVÉE BLESSING SCHOOL      │
│       Tél : 066 61 66 10 / 066 38 42 50 │
│              Okala CICIBA                │
│                                          │
│          [LOGO DE L'ÉCOLE]               │
│                                          │
│      Travail - Rigueur - Discipline      │
│                                          │
│      ┌─ LIVRET SCOLAIRE ─┐              │
│                                          │
│  ┌──────────────────────────────────┐   │
│  │ ELEVE              [Photo]       │   │
│  │ Noms : AMVAME                    │   │
│  │ Prénoms : elya maria             │   │
│  │ Date de naissance : ...          │   │
│  │ Classe : 4 ans                   │   │
│  │ Nom de l'Enseignat(e) : ...      │   │
│  │ Année Scolaire : 2024/2025       │   │
│  └──────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

## 🔍 Vérification

Pour vérifier que tout fonctionne :

1. Allez dans **"Évaluations → Notes Préprimaire"**
2. Sélectionnez une classe
3. Cliquez sur **"Voir Bulletin"** pour un élève
4. Cliquez sur **"Télécharger PDF"**
5. Vérifiez que :
   - ✅ Le logo de l'école s'affiche correctement
   - ✅ Le nom de l'école est correct
   - ✅ Les coordonnées sont complètes
   - ✅ La devise est affichée

## 🚨 Dépannage

### **Le logo ne s'affiche pas**

**Problème** : Le logo n'apparaît pas dans le PDF, seulement les initiales de l'école.

**Solutions** :
1. Vérifiez que le fichier a bien été téléchargé (regardez dans le formulaire de paramètres)
2. Assurez-vous que le dossier `storage/app/public/school` existe
3. Exécutez la commande :
   ```bash
   php artisan storage:link
   ```
4. Vérifiez que le fichier est accessible via :
   ```
   http://127.0.0.1:8000/storage/school/[nom-du-fichier]
   ```

### **Les informations ne s'affichent pas**

**Problème** : Les informations affichées sont incorrectes ou vides.

**Solutions** :
1. Vérifiez que les champs ont bien été remplis et sauvegardés
2. Nettoyez le cache :
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```
3. Rechargez la page de paramètres et vérifiez les valeurs

### **Erreur CORS avec le logo**

**Problème** : Message d'erreur "Failed to load image" dans la console.

**Solutions** :
1. Le logo doit être hébergé sur le même domaine que l'application
2. Si vous utilisez un CDN externe, assurez-vous qu'il autorise CORS
3. Privilégiez le téléchargement direct du logo dans l'application

## 📞 Support

Si vous rencontrez des difficultés, consultez :
- La documentation complète dans `DOCUMENTATION_EGESCO.txt`
- Le README principal du projet

## ✅ Checklist Finale

Avant de générer les bulletins officiels, vérifiez :

- [ ] Le logo de l'école est téléchargé et visible
- [ ] Le nom de l'école primaire est correct
- [ ] Les numéros de téléphone sont à jour
- [ ] La ville est correcte
- [ ] La devise de l'école est renseignée
- [ ] L'année scolaire est correcte
- [ ] Un test de génération PDF a été effectué
- [ ] Le bulletin généré est conforme au modèle officiel

---

**Date de création** : Novembre 2024  
**Version** : 1.0  
**Système** : EGESCO - Gestion Scolaire

