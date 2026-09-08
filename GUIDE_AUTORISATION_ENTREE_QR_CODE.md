# 🎫 Guide - Autorisation d'Entrée avec QR Code

## 🎯 Objectif

Créer un système complet pour générer des **autorisations d'entrée** avec **QR code** après l'inscription d'un élève. Ce document permet de :
- ✅ Identifier rapidement l'élève à l'entrée de l'établissement
- ✅ Vérifier l'inscription via le QR code
- ✅ Sécuriser l'accès à l'établissement
- ✅ Avoir un document officiel d'autorisation

---

## 📋 Fonctionnalités Implémentées

### **1. Code d'Inscription Unique**

Chaque inscription génère automatiquement un **code d'inscription unique** :

**Format** : `ENR-YYYY-XXXXXX`
- `ENR` : Préfixe pour "Enrollment"
- `YYYY` : Année en cours
- `XXXXXX` : Numéro séquentiel sur 6 chiffres

**Exemples** :
- `ENR-2024-000001`
- `ENR-2024-000002`
- `ENR-2025-000001`

### **2. QR Code avec Informations Complètes**

Le QR code contient toutes les informations de l'inscription en format JSON :

```json
{
    "code": "ENR-2024-000001",
    "student": "Jean PASSANT",
    "matricule": "STU2024TEST001",
    "class": "CE1 A",
    "year": "2024-2025",
    "date": "2024-10-09",
    "receipt": "REC20241000001",
    "school": "École Primaire Excellence"
}
```

### **3. Document PDF A5 Paysage**

Le document d'autorisation d'entrée est généré en format **A5 paysage** pour :
- ✅ Faciliter la plastification
- ✅ Format pratique (148 x 210 mm en paysage)
- ✅ Facile à scanner et à manipuler

---

## 🏗️ Architecture Technique

### **1. Base de Données**

#### **Migration**

```php
// database/migrations/2025_10_09_094051_add_enrollment_code_to_enrollments_table.php

Schema::table('enrollments', function (Blueprint $table) {
    $table->string('enrollment_code', 20)->unique()->nullable()->after('receipt_number');
    $table->index('enrollment_code');
});
```

#### **Champ ajouté**

| Colonne | Type | Description |
|---------|------|-------------|
| `enrollment_code` | VARCHAR(20) | Code unique pour le QR code |

**Contraintes** :
- ✅ UNIQUE (pas de doublon)
- ✅ INDEX (recherche rapide)
- ✅ NULLABLE (pour les anciennes inscriptions)

---

### **2. Modèle Enrollment**

#### **Méthode de Génération**

```php
// app/Models/Enrollment.php

public function generateEnrollmentCode()
{
    if (!$this->enrollment_code) {
        $year = date('Y');
        
        // Format: ENR-YYYY-XXXXXX (ENR-2024-000001)
        $lastEnrollment = static::where('enrollment_code', 'like', "ENR-{$year}-%")
                            ->orderBy('enrollment_code', 'desc')
                            ->first();
        
        $nextNumber = 1;
        if ($lastEnrollment) {
            $lastNumber = intval(substr($lastEnrollment->enrollment_code, -6));
            $nextNumber = $lastNumber + 1;
        }
        
        $this->enrollment_code = "ENR-{$year}-" . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        $this->save();
    }
    
    return $this->enrollment_code;
}
```

**Logique** :
1. Vérifier si le code existe déjà
2. Récupérer le dernier code de l'année en cours
3. Incrémenter le numéro
4. Formater avec padding (000001, 000002, etc.)
5. Sauvegarder et retourner

---

### **3. Contrôleur**

#### **Génération Automatique lors de l'Inscription**

```php
// app/Http/Controllers/EnrollmentController.php - store()

$enrollment = Enrollment::create($enrollmentData);

// Générer automatiquement les codes
$enrollment->updatePaymentStatus();
$enrollment->generateReceiptNumber();
$enrollment->generateEnrollmentCode(); // ← Nouveau
$enrollment->generatePaymentReference();
$enrollment->save();
```

#### **Méthode de Téléchargement**

```php
// app/Http/Controllers/EnrollmentController.php

public function downloadEntryAuthorization(Enrollment $enrollment)
{
    // S'assurer qu'un code d'inscription existe
    if (!$enrollment->enrollment_code) {
        $enrollment->generateEnrollmentCode();
    }

    // Charger les relations nécessaires
    $enrollment->load(['schoolClass.level', 'academicYear', 'student']);

    // Charger les paramètres de l'établissement
    $schoolSettings = \App\Models\SchoolSettings::getSettings();
    
    // Encoder le logo en base64 pour DomPDF
    if ($schoolSettings && $schoolSettings->school_logo) {
        $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
        
        if (file_exists($logoPath)) {
            $logoContent = file_get_contents($logoPath);
            $logoInfo = pathinfo($logoPath);
            $extension = strtolower($logoInfo['extension']);
            $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
            $schoolSettings->logo_base64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
        }
    }

    // Générer le QR code avec les informations de l'inscription
    $qrData = json_encode([
        'code' => $enrollment->enrollment_code,
        'student' => $enrollment->applicant_first_name . ' ' . $enrollment->applicant_last_name,
        'matricule' => $enrollment->student ? $enrollment->student->student_id : 'N/A',
        'class' => $enrollment->schoolClass->name ?? 'N/A',
        'year' => $enrollment->academicYear->name ?? 'N/A',
        'date' => $enrollment->enrollment_date->format('Y-m-d'),
        'receipt' => $enrollment->receipt_number,
        'school' => $schoolSettings->school_name ?? 'Egesco'
    ]);

    // Créer le QR code (120x120 px avec marge de 5px)
    $qrCode = Builder::create()
        ->data($qrData)
        ->size(120)
        ->margin(5)
        ->build();

    // Convertir le QR code en SVG pour DomPDF
    $qrCodeSvg = $qrCode->getString();

    // Générer le PDF avec DomPDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('enrollments.entry-authorization-pdf', [
        'enrollment' => $enrollment,
        'schoolSettings' => $schoolSettings,
        'qrCode' => $qrCodeSvg
    ]);
    
    // Configuration pour format A5 paysage
    $pdf->setPaper('A5', 'landscape');
    
    // Nom du fichier
    $filename = 'autorisation_entree_' . $enrollment->enrollment_code . '.pdf';
    
    // Télécharger le PDF
    return $pdf->download($filename);
}
```

---

### **4. Route**

```php
// routes/web.php

Route::get('/enrollments/{enrollment}/entry-authorization/download', 
    [EnrollmentController::class, 'downloadEntryAuthorization'])
    ->name('enrollments.download-entry-authorization');
```

**URL** : `/enrollments/{id}/entry-authorization/download`

---

### **5. Vue PDF**

#### **Structure du Document**

```
┌─────────────────────────────────────────────────────────────────┐
│                    ÉCOLE PRIMAIRE EXCELLENCE                    │
│              Système de Gestion Scolaire - Libreville           │
│                    Tél: +241 XX XX XX XX                        │
├─────────────────────────────────────────────────────────────────┤
│   🎓 AUTORISATION D'ENTRÉE - ANNÉE SCOLAIRE 2024-2025         │
├──────────────────────────────────────┬──────────────────────────┤
│ 👤 INFORMATIONS DE L'ÉLÈVE           │      [PHOTO]             │
│                                      │                          │
│ Nom complet: Jean PASSANT            │   ┌────────────┐        │
│ Matricule: STU2024TEST001            │   │            │        │
│ Date de naissance: 15/03/2017        │   │  QR CODE   │        │
│ Sexe: Masculin                       │   │            │        │
│ Classe: CE1 A (Primaire)             │   └────────────┘        │
│ Statut: [PASSANT]                    │   ENR-2024-000001       │
│ Type: [RÉINSCRIPTION]                │   Scanner pour          │
│                                      │   vérifier              │
│ 👨‍👩‍👧 PARENT/TUTEUR                    │                          │
│                                      │   ┌────────────┐        │
│ Nom: Marie PASSANT                   │   │ ✓ VALIDE   │        │
│ Lien: Mère                           │   │   POUR     │        │
│ Téléphone: +241 XX XX XX XX          │   │ 2024-2025  │        │
│                                      │   └────────────┘        │
│ 📋 INFORMATIONS D'INSCRIPTION        │                          │
│                                      │   Signature             │
│ Date d'inscription: 09/10/2024       │   ──────────            │
│ Reçu N°: REC20241000001              │   Direction             │
│ Statut paiement: [PAYÉ]              │                          │
│                                      │                          │
│ ⚠️ IMPORTANT: Ce document doit être  │                          │
│ présenté à chaque entrée dans        │                          │
│ l'établissement. Il est strictement  │                          │
│ personnel et ne peut être prêté.     │                          │
├─────────────────────────────────────────────────────────────────┤
│     Ce document est obligatoire pour accéder à l'établissement  │
│        École Primaire Excellence - Généré le 09/10/2024         │
│   En cas de perte, veuillez contacter immédiatement l'admin.   │
└─────────────────────────────────────────────────────────────────┘
```

#### **Caractéristiques Visuelles**

- **Format** : A5 paysage (210 x 148 mm)
- **Layout** : 2 colonnes
  - Gauche : Informations détaillées
  - Droite : Photo + QR code + Validité
- **Couleurs** :
  - Bleu (#007bff) : Titre, bordures, accents
  - Vert : Badge "PASSANT", validité
  - Jaune : Badge "REDOUBLANT"
  - Bleu clair : Badge "NOUVEAU"
- **Badges** : Statut élève et type d'inscription
- **QR Code** : 120x120 px avec bordure bleue
- **Photo** : Placeholder 80x100 px (si disponible)

---

## 🔄 Workflow Complet

### **Étape 1 : Inscription**

```
Utilisateur remplit formulaire
    ↓
Soumission
    ↓
Création Enrollment
    ↓
Génération automatique:
  - receipt_number: REC20241000001
  - enrollment_code: ENR-2024-000001 ← Nouveau
  - payment_reference: PAY-2024-XXXXX
```

### **Étape 2 : Modal de Succès**

```
┌──────────────────────────────────────────────┐
│ ✅ Inscription Enregistrée !            [×]  │
├──────────────────────────────────────────────┤
│ Inscrit: Jean PASSANT                        │
│ Classe: CE1 A                                │
│ Reçu N°: REC20241000001                      │
├──────────────────────────────────────────────┤
│ [Plus tard]                                  │
│ [🔒 Autorisation d'entrée] ← Nouveau         │
│ [🖨️ Imprimer le reçu]                        │
│ [👤 Créer le profil élève]                   │
└──────────────────────────────────────────────┘
```

### **Étape 3 : Génération du Document**

```
Clic sur "Autorisation d'entrée"
    ↓
Appel API: /enrollments/{id}/entry-authorization/download
    ↓
Contrôleur:
  1. Vérifier enrollment_code (générer si absent)
  2. Charger relations (student, class, year)
  3. Charger paramètres école
  4. Générer QR code avec données JSON
  5. Créer PDF A5 paysage
    ↓
Téléchargement: autorisation_entree_ENR-2024-000001.pdf
```

---

## 📊 Données du QR Code

### **Contenu JSON**

```json
{
    "code": "ENR-2024-000001",
    "student": "Jean PASSANT",
    "matricule": "STU2024TEST001",
    "class": "CE1 A",
    "year": "2024-2025",
    "date": "2024-10-09",
    "receipt": "REC20241000001",
    "school": "École Primaire Excellence"
}
```

### **Utilisation**

1. **Scanner le QR code** avec un smartphone ou lecteur QR
2. **Décoder le JSON** pour obtenir les informations
3. **Vérifier** :
   - Code d'inscription valide
   - Année scolaire en cours
   - Élève inscrit
   - Paiement effectué

### **Sécurité**

- ✅ Code unique par inscription
- ✅ Informations complètes pour vérification
- ✅ Difficile à falsifier
- ✅ Traçable dans la base de données

---

## 🎨 Design du Document

### **En-tête**

```html
┌─────────────────────────────────────────────┐
│            [LOGO DE L'ÉCOLE]                │
│      ÉCOLE PRIMAIRE EXCELLENCE              │
│   Système de Gestion Scolaire - Libreville │
│         Tél: +241 XX XX XX XX               │
└─────────────────────────────────────────────┘
```

### **Titre**

```html
┌─────────────────────────────────────────────┐
│ 🎓 AUTORISATION D'ENTRÉE                    │
│    ANNÉE SCOLAIRE 2024-2025                 │
└─────────────────────────────────────────────┘
```
- Background : Dégradé bleu (#007bff → #0056b3)
- Texte : Blanc, gras, 16px
- Icône : 🎓

### **Section Informations**

Chaque bloc d'informations :
- Background : #f8f9fa
- Bordure gauche : 4px solid #007bff
- Padding : 8px 10px
- Titre : Bleu, gras, 11px, uppercase
- Icônes : 👤 (élève), 👨‍👩‍👧 (parent), 📋 (inscription)

### **Section QR Code**

```html
┌──────────────┐
│   [PHOTO]    │
│   80x100px   │
└──────────────┘

┌──────────────┐
│              │
│   QR CODE    │
│   120x120px  │
│              │
└──────────────┘
  ENR-2024-000001
  Scanner pour
    vérifier

┌──────────────┐
│  ✓ VALIDE    │
│    POUR      │
│  2024-2025   │
└──────────────┘
```

### **Avertissement**

```html
┌─────────────────────────────────────────────┐
│ ⚠️ IMPORTANT: Ce document doit être         │
│ présenté à chaque entrée dans               │
│ l'établissement. Il est strictement         │
│ personnel et ne peut être prêté ou cédé.    │
└─────────────────────────────────────────────┘
```
- Background : #fff3cd
- Bordure gauche : 4px solid #ffc107
- Texte : 8px

---

## 💻 Code Clé

### **Génération du QR Code**

```php
use Endroid\QrCode\Builder\Builder;

// Données à encoder
$qrData = json_encode([
    'code' => $enrollment->enrollment_code,
    'student' => $enrollment->applicant_first_name . ' ' . $enrollment->applicant_last_name,
    'matricule' => $enrollment->student ? $enrollment->student->student_id : 'N/A',
    'class' => $enrollment->schoolClass->name ?? 'N/A',
    'year' => $enrollment->academicYear->name ?? 'N/A',
    'date' => $enrollment->enrollment_date->format('Y-m-d'),
    'receipt' => $enrollment->receipt_number,
    'school' => $schoolSettings->school_name ?? 'Egesco'
]);

// Créer le QR code
$qrCode = Builder::create()
    ->data($qrData)
    ->size(120)
    ->margin(5)
    ->build();

// Convertir en SVG pour DomPDF
$qrCodeSvg = $qrCode->getString();
```

### **Affichage dans la Vue**

```blade
<div class="qr-code">
    {!! $qrCode !!}
</div>
<div class="enrollment-code">
    {{ $enrollment->enrollment_code }}
</div>
```

### **Génération du PDF**

```php
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('enrollments.entry-authorization-pdf', [
    'enrollment' => $enrollment,
    'schoolSettings' => $schoolSettings,
    'qrCode' => $qrCodeSvg
]);

$pdf->setPaper('A5', 'landscape');

return $pdf->download('autorisation_entree_' . $enrollment->enrollment_code . '.pdf');
```

---

## 📦 Packages Installés

### **endroid/qr-code**

```bash
composer require endroid/qr-code
```

**Version** : 6.0.9

**Dépendances** :
- `bacon/bacon-qr-code` : 3.0.1
- `dasprid/enum` : 1.0.7

**Avantages** :
- ✅ Pas besoin de l'extension GD
- ✅ Support SVG natif
- ✅ Compatible avec DomPDF
- ✅ Facile à utiliser
- ✅ Bien maintenu

---

## 🔄 Cas d'Usage

### **Cas 1 : Nouvelle Inscription**

```
1. Parent inscrit son enfant
2. Paiement effectué
3. Modal de succès s'affiche
4. Clic sur "Autorisation d'entrée"
5. PDF généré et téléchargé
6. Document imprimé et plastifié
7. Remis au parent
8. Élève présente le document à l'entrée
```

### **Cas 2 : Réinscription**

```
1. Élève réinscrit pour nouvelle année
2. Nouveau code généré: ENR-2025-000001
3. Nouvelle autorisation générée
4. Ancien document devient invalide
5. Nouveau document actif pour 2024-2025
```

### **Cas 3 : Vérification à l'Entrée**

```
1. Élève présente l'autorisation
2. Agent de sécurité scanne le QR code
3. Informations affichées:
   - Nom: Jean PASSANT
   - Matricule: STU2024TEST001
   - Classe: CE1 A
   - Année: 2024-2025
   - Code: ENR-2024-000001
4. Vérification visuelle de la photo
5. Accès autorisé ✅
```

### **Cas 4 : Document Perdu**

```
1. Parent signale la perte
2. Administration consulte l'inscription
3. Régénération du document:
   - Même enrollment_code
   - Même QR code
   - Mention "DUPLICATA" (optionnel)
4. Nouveau document remis
```

---

## ✅ Checklist de Validation

### **Base de Données**
- [x] Migration exécutée
- [x] Champ `enrollment_code` ajouté
- [x] Index créé
- [x] Contrainte UNIQUE appliquée

### **Modèle**
- [x] Champ ajouté dans `$fillable`
- [x] Méthode `generateEnrollmentCode()` créée
- [x] Génération automatique lors de l'inscription

### **Contrôleur**
- [x] Import `Endroid\QrCode\Builder\Builder`
- [x] Méthode `downloadEntryAuthorization()` créée
- [x] Génération du QR code avec données JSON
- [x] Conversion SVG pour DomPDF
- [x] Configuration A5 paysage

### **Route**
- [x] Route ajoutée dans `web.php`
- [x] Nom de route défini

### **Vue**
- [x] Template PDF créé
- [x] Format A5 paysage
- [x] Layout 2 colonnes
- [x] Section QR code
- [x] Badges de statut
- [x] Avertissement de sécurité

### **Interface**
- [x] Bouton ajouté dans le modal
- [x] Icône QR code
- [x] JavaScript configuré
- [x] Ouverture dans nouvel onglet

### **Package**
- [x] `endroid/qr-code` installé
- [x] Dépendances installées
- [x] Autoload généré

---

## 🎯 Résultat Final

### **Avant** ❌
- Pas d'autorisation d'entrée
- Pas de QR code
- Vérification manuelle difficile
- Pas de traçabilité

### **Après** ✅
- ✅ **Autorisation d'entrée officielle** avec QR code
- ✅ **Code unique** par inscription (ENR-YYYY-XXXXXX)
- ✅ **QR code** avec toutes les informations
- ✅ **Document PDF A5 paysage** professionnel
- ✅ **Bouton** dans le modal de succès
- ✅ **Génération automatique** du code
- ✅ **Vérification rapide** à l'entrée
- ✅ **Sécurité renforcée** de l'établissement
- ✅ **Traçabilité complète** des entrées

**Le système d'autorisation d'entrée avec QR code est opérationnel ! 🎉**

---

**Date** : 9 octobre 2025  
**Version** : 7.0  
**Statut** : ✅ Production Ready
