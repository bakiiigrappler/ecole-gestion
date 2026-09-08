# 📄 Guide - Reçu d'Inscription Amélioré

## 🎯 Objectif

Mettre à jour le reçu d'inscription pour refléter toutes les nouvelles informations :
- ✅ Type d'inscription (Nouvelle / Réinscription)
- ✅ Statut élève (Nouveau / Passant / Redoublant)
- ✅ Détail complet des frais (base + optionnels)
- ✅ Informations de réinscription
- ✅ Résultat année précédente

---

## 📋 Améliorations Apportées

### **1. Titre Dynamique**

Le titre du reçu change selon le type d'inscription :

| Type | Titre |
|------|-------|
| Nouvelle inscription | **REÇU D'INSCRIPTION** |
| Réinscription | **REÇU DE RÉINSCRIPTION** |

```blade
<div class="receipt-title">
    @if($enrollment->is_reinscription)
        REÇU DE RÉINSCRIPTION
    @else
        REÇU D'INSCRIPTION
    @endif
</div>
```

---

### **2. Matricule de l'Élève**

Pour les réinscriptions, le matricule de l'élève est affiché :

```blade
@if($enrollment->student && $enrollment->student->student_id)
<div class="row">
    <span><strong>Matricule:</strong> {{ $enrollment->student->student_id }}</span>
</div>
@endif
```

**Exemple** :
```
Nom: Jean PASSANT          Sexe: M
Matricule: STU2024TEST001
Classe: CE1 A              Cycle: Primaire
```

---

### **3. Statut de l'Élève**

Le statut de l'élève est affiché avec un badge coloré :

| Statut | Badge | Couleur |
|--------|-------|---------|
| Nouveau | NOUVEAU | Bleu (#cfe2ff) |
| Passant | PASSANT | Vert (#d1e7dd) |
| Redoublant | REDOUBLANT | Jaune (#fff3cd) |

```blade
@if($enrollment->student_status)
<div class="row">
    <span><strong>Statut élève:</strong> 
        @if($enrollment->student_status === 'nouveau')
            <span class="status" style="background: #cfe2ff; color: #084298;">NOUVEAU</span>
        @elseif($enrollment->student_status === 'passant')
            <span class="status" style="background: #d1e7dd; color: #0f5132;">PASSANT</span>
        @elseif($enrollment->student_status === 'redoublant')
            <span class="status" style="background: #fff3cd; color: #664d03;">REDOUBLANT</span>
        @endif
    </span>
</div>
@endif
```

**Exemple** :
```
Statut élève: [PASSANT] (badge vert)
```

---

### **4. Résultat Année Précédente**

Pour les réinscriptions, affichage du résultat et de la moyenne :

```blade
@if($enrollment->is_reinscription && $enrollment->previous_year_result)
<div class="row">
    <span><strong>Résultat année précédente:</strong> {{ ucfirst($enrollment->previous_year_result) }}</span>
    @if($enrollment->previous_year_average)
        <span><strong>Moyenne:</strong> {{ number_format($enrollment->previous_year_average, 2) }}/20</span>
    @endif
</div>
@endif
```

**Exemple** :
```
Résultat année précédente: Admis    Moyenne: 14.50/20
```

---

### **5. Détail Complet des Frais**

Le reçu affiche maintenant le détail complet des frais :

#### **Structure**

1. **Frais de scolarité de base** (Obligatoire)
2. **Frais optionnels sélectionnés** (Liste détaillée)
3. **Total des frais**
4. **Montant payé**
5. **Reste à payer**

```blade
@if($enrollment->enrollmentFees && $enrollment->enrollmentFees->count() > 0)
    @php
        $baseTuitionFee = $enrollment->enrollmentFees->where('fee.is_base_tuition', true)->first();
        $otherFees = $enrollment->enrollmentFees->where('fee.is_base_tuition', '!=', true);
    @endphp
    
    <!-- Frais de base -->
    @if($baseTuitionFee)
    <div class="row">
        <span>
            <strong>{{ $baseTuitionFee->fee->name }}</strong>
            <span style="font-size: 8px; color: #007bff;">(Obligatoire)</span>
        </span>
        <span class="amount">{{ number_format($baseTuitionFee->amount, 0, ',', ' ') }} FCFA</span>
    </div>
    @endif
    
    <!-- Frais optionnels -->
    @if($otherFees->count() > 0)
    <div style="margin: 5px 0; padding-top: 3px; border-top: 1px dashed #ccc;">
        <div style="font-size: 9px; color: #666;"><em>Frais optionnels sélectionnés :</em></div>
        @foreach($otherFees as $enrollmentFee)
        <div class="row" style="font-size: 10px;">
            <span>• {{ $enrollmentFee->fee->name }}</span>
            <span>{{ number_format($enrollmentFee->amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endforeach
    </div>
    @endif
    
    <!-- Total -->
    <div class="row" style="border-top: 1px solid #007bff;">
        <span><strong>Total des frais:</strong></span>
        <span class="amount"><strong>{{ number_format($enrollment->total_fees, 0, ',', ' ') }} FCFA</strong></span>
    </div>
@endif
```

#### **Exemple de Rendu**

```
┌─────────────────────────────────────────────────────┐
│ DÉTAIL DES FRAIS                                    │
├─────────────────────────────────────────────────────┤
│ Frais de scolarité - Primaire (Obligatoire)        │
│                                        205,000 FCFA │
├─────────────────────────────────────────────────────┤
│ Frais optionnels sélectionnés :                     │
│ • Uniforme scolaire                     25,000 FCFA │
│ • Fournitures scolaires                 15,000 FCFA │
│ • Assurance scolaire                    10,000 FCFA │
├═════════════════════════════════════════════════════┤
│ Total des frais:                       255,000 FCFA │
│ Montant payé:                          255,000 FCFA │
│ Reste à payer:                               0 FCFA │
└─────────────────────────────────────────────────────┘
```

---

### **6. Informations de Paiement Détaillées**

Affichage complet des informations de paiement :

```blade
@if($enrollment->payment_method)
<div style="margin-top: 8px; font-size: 10px; padding-top: 5px; border-top: 1px dashed #ccc;">
    <strong>Mode de paiement:</strong> {{ $enrollment->getPaymentMethodLabelAttribute() }}
    @if($enrollment->payment_reference)
        <br><strong>Référence:</strong> {{ $enrollment->payment_reference }}
    @endif
    @if($enrollment->payment_date)
        <br><strong>Date de paiement:</strong> {{ $enrollment->payment_date->format('d/m/Y') }}
    @endif
</div>
@endif
```

**Exemple** :
```
Mode de paiement: Virement bancaire
Référence: VIR-2024-001
Date de paiement: 08/10/2024
```

---

### **7. Couleur Dynamique du Solde**

Le reste à payer est coloré selon son montant :

| Condition | Couleur | Signification |
|-----------|---------|---------------|
| Solde > 0 | Rouge (#dc3545) | Paiement incomplet |
| Solde = 0 | Vert (#28a745) | Paiement complet |

```blade
<div class="row total-line">
    <span><strong>Reste à payer:</strong></span>
    @if($enrollment->balance_due > 0)
        <span class="amount" style="color: #dc3545;">
            <strong>{{ number_format($enrollment->balance_due, 0, ',', ' ') }} FCFA</strong>
        </span>
    @else
        <span class="amount" style="color: #28a745;">
            <strong>{{ number_format($enrollment->balance_due, 0, ',', ' ') }} FCFA</strong>
        </span>
    @endif
</div>
```

---

### **8. Note Spéciale pour Réinscriptions**

Une note informative est ajoutée pour les réinscriptions :

```blade
@if($enrollment->is_reinscription)
<div style="margin-top: 10px; padding: 5px; background: #e7f3ff; border-left: 3px solid #007bff; font-size: 9px;">
    <strong>ℹ️ Réinscription</strong><br>
    Cet élève a été réinscrit pour l'année scolaire {{ $enrollment->academicYear->name }}.
    @if($enrollment->previous_class_id)
        @php
            $previousClass = \App\Models\SchoolClass::find($enrollment->previous_class_id);
        @endphp
        @if($previousClass)
            <br>Classe précédente : {{ $previousClass->name }}
        @endif
    @endif
</div>
@endif
```

**Exemple** :
```
┌─────────────────────────────────────────────────────┐
│ ℹ️ Réinscription                                    │
│ Cet élève a été réinscrit pour l'année scolaire    │
│ 2024-2025.                                          │
│ Classe précédente : CP B                            │
└─────────────────────────────────────────────────────┘
```

---

### **9. Pied de Page Amélioré**

Pour les réinscriptions, une mention spéciale est ajoutée au pied de page :

```blade
<div class="footer">
    <p><strong>Ce reçu fait foi de paiement - À conserver précieusement</strong></p>
    <p>{{ $schoolSettings->school_name ?? 'Egesco' }} - {{ now()->format('d/m/Y H:i') }}</p>
    @if($enrollment->is_reinscription)
        <p style="font-size: 8px; color: #007bff;">
            ✓ Réinscription | Mise à jour des informations élève effectuée
        </p>
    @endif
</div>
```

---

## 📊 Comparaison Avant/Après

### **Avant** ❌

```
┌─────────────────────────────────────────────────────┐
│ REÇU D'INSCRIPTION                                  │
├─────────────────────────────────────────────────────┤
│ Nom: Jean PASSANT                                   │
│ Classe: CE1 A                                       │
│                                                     │
│ Frais d'inscription:              205,000 FCFA      │
│ Montant payé:                     205,000 FCFA      │
│ Reste à payer:                          0 FCFA      │
└─────────────────────────────────────────────────────┘
```

**Problèmes** :
- ❌ Pas de distinction nouvelle inscription / réinscription
- ❌ Pas de matricule
- ❌ Pas de statut élève
- ❌ Pas de détail des frais
- ❌ Pas d'info sur l'année précédente

---

### **Après** ✅

```
┌─────────────────────────────────────────────────────┐
│ REÇU DE RÉINSCRIPTION                               │
├─────────────────────────────────────────────────────┤
│ N° REC-2024-002                  Année: 2024-2025   │
│ Date: 08/10/2024                 Statut: [PAYÉ]     │
├─────────────────────────────────────────────────────┤
│ ÉLÈVE                                               │
│ Nom: Jean PASSANT                Sexe: M            │
│ Matricule: STU2024TEST001                           │
│ Classe: CE1 A                    Cycle: Primaire    │
│ Né(e) le: 15/03/2017                                │
│ Statut élève: [PASSANT] (badge vert)               │
│ Résultat année précédente: Admis  Moyenne: 14.50/20│
├─────────────────────────────────────────────────────┤
│ PARENT/TUTEUR                                       │
│ Nom: Marie PASSANT               Lien: Mère         │
│ Tél: +241 XX XX XX XX                               │
├─────────────────────────────────────────────────────┤
│ DÉTAIL DES FRAIS                                    │
│                                                     │
│ Frais de scolarité - Primaire (Obligatoire)        │
│                                        205,000 FCFA │
│ ─────────────────────────────────────────────────  │
│ Frais optionnels sélectionnés :                     │
│ • Uniforme scolaire                     25,000 FCFA │
│ • Fournitures scolaires                 15,000 FCFA │
│ • Assurance scolaire                    10,000 FCFA │
│ ═════════════════════════════════════════════════  │
│ Total des frais:                       255,000 FCFA │
│ Montant payé:                          255,000 FCFA │
│ Reste à payer:                               0 FCFA │
│                                                     │
│ Mode de paiement: Virement bancaire                 │
│ Référence: VIR-2024-001                             │
│ Date de paiement: 08/10/2024                        │
├─────────────────────────────────────────────────────┤
│ ℹ️ Réinscription                                    │
│ Cet élève a été réinscrit pour l'année scolaire    │
│ 2024-2025.                                          │
│ Classe précédente : CP B                            │
├─────────────────────────────────────────────────────┤
│                                  Cachet et signature│
│                                  ─────────────────  │
│                                      Administration │
├─────────────────────────────────────────────────────┤
│ Ce reçu fait foi de paiement - À conserver          │
│ Egesco - 08/10/2024 15:30                           │
│ ✓ Réinscription | Mise à jour des informations     │
│   élève effectuée                                   │
└─────────────────────────────────────────────────────┘
```

**Améliorations** :
- ✅ Titre "REÇU DE RÉINSCRIPTION"
- ✅ Matricule affiché
- ✅ Statut élève avec badge coloré
- ✅ Résultat et moyenne année précédente
- ✅ Détail complet des frais (base + optionnels)
- ✅ Informations de paiement complètes
- ✅ Note spéciale réinscription
- ✅ Classe précédente mentionnée
- ✅ Mention mise à jour élève

---

## 🔄 Cas d'Usage

### **Cas 1 : Nouvelle Inscription**

**Contexte** : Premier enfant, première inscription

**Reçu** :
```
REÇU D'INSCRIPTION
N° REC-2024-001
Date: 08/10/2024

ÉLÈVE
Nom: Sophie NOUVEAU          Sexe: F
Classe: CP A                 Cycle: Primaire
Né(e) le: 20/05/2018
Statut élève: [NOUVEAU]

DÉTAIL DES FRAIS
Frais de scolarité - Primaire (Obligatoire)  205,000 FCFA
─────────────────────────────────────────────────────
Frais optionnels sélectionnés :
• Uniforme scolaire                           25,000 FCFA
═════════════════════════════════════════════════════
Total des frais:                             230,000 FCFA
Montant payé:                                230,000 FCFA
Reste à payer:                                     0 FCFA
```

---

### **Cas 2 : Réinscription Élève Passant**

**Contexte** : Élève qui passe en classe supérieure

**Reçu** :
```
REÇU DE RÉINSCRIPTION
N° REC-2024-002
Date: 08/10/2024

ÉLÈVE
Nom: Jean PASSANT            Sexe: M
Matricule: STU2024TEST001
Classe: CE1 A                Cycle: Primaire
Né(e) le: 15/03/2017
Statut élève: [PASSANT]
Résultat année précédente: Admis    Moyenne: 14.50/20

DÉTAIL DES FRAIS
Frais de scolarité - Primaire (Obligatoire)  205,000 FCFA
─────────────────────────────────────────────────────
Frais optionnels sélectionnés :
• Fournitures scolaires                       15,000 FCFA
═════════════════════════════════════════════════════
Total des frais:                             220,000 FCFA
Montant payé:                                220,000 FCFA
Reste à payer:                                     0 FCFA

ℹ️ Réinscription
Cet élève a été réinscrit pour l'année scolaire 2024-2025.
Classe précédente : CP B

✓ Réinscription | Mise à jour des informations élève effectuée
```

---

### **Cas 3 : Réinscription Élève Redoublant**

**Contexte** : Élève qui redouble

**Reçu** :
```
REÇU DE RÉINSCRIPTION
N° REC-2024-003
Date: 08/10/2024

ÉLÈVE
Nom: Marc REDOUBLANT         Sexe: M
Matricule: STU2024TEST002
Classe: CP B                 Cycle: Primaire
Né(e) le: 10/08/2017
Statut élève: [REDOUBLANT]
Résultat année précédente: Redouble    Moyenne: 8.20/20

DÉTAIL DES FRAIS
Frais de scolarité - Primaire (Obligatoire)  205,000 FCFA
─────────────────────────────────────────────────────
Frais optionnels sélectionnés :
• Soutien scolaire                            30,000 FCFA
═════════════════════════════════════════════════════
Total des frais:                             235,000 FCFA
Montant payé:                                100,000 FCFA
Reste à payer:                               135,000 FCFA

⚠️ Solde à régler avant le 30/10/2024

ℹ️ Réinscription
Cet élève a été réinscrit pour l'année scolaire 2024-2025.
Classe précédente : CP B

✓ Réinscription | Mise à jour des informations élève effectuée
```

---

## 💻 Code Technique

### **Contrôleur - Chargement des Relations**

```php
public function downloadReceipt(Enrollment $enrollment)
{
    // S'assurer qu'un numéro de reçu existe
    if (!$enrollment->receipt_number) {
        $enrollment->generateReceiptNumber();
    }

    // Charger les relations nécessaires
    $enrollment->load([
        'schoolClass.level', 
        'academicYear', 
        'student',              // ← Nouveau : pour matricule
        'enrollmentFees.fee'    // ← Nouveau : pour détail frais
    ]);

    // Charger les paramètres de l'établissement
    $schoolSettings = \App\Models\SchoolSettings::getSettings();
    
    // ... (gestion logo) ...

    // Générer le PDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'enrollments.receipt-pdf', 
        compact('enrollment', 'schoolSettings')
    );
    
    $pdf->setPaper('A5', 'portrait');
    
    $filename = 'recu_inscription_' . $enrollment->receipt_number . '.pdf';
    
    return $pdf->download($filename);
}
```

---

## 📋 Checklist de Validation

### **Informations Générales**
- [ ] Titre change selon type (Inscription / Réinscription)
- [ ] Numéro de reçu affiché
- [ ] Date d'inscription affichée
- [ ] Année scolaire affichée
- [ ] Statut de paiement affiché (badge coloré)

### **Informations Élève**
- [ ] Nom et prénom affichés
- [ ] Sexe affiché
- [ ] Matricule affiché (si réinscription)
- [ ] Classe et cycle affichés
- [ ] Date de naissance affichée
- [ ] Statut élève affiché avec badge coloré
- [ ] Résultat année précédente affiché (si réinscription)
- [ ] Moyenne affichée (si réinscription)

### **Détail des Frais**
- [ ] Frais de scolarité de base affiché avec mention "(Obligatoire)"
- [ ] Frais optionnels listés séparément
- [ ] Total des frais calculé correctement
- [ ] Montant payé affiché en vert
- [ ] Reste à payer affiché (rouge si > 0, vert si = 0)

### **Informations de Paiement**
- [ ] Mode de paiement affiché
- [ ] Référence de paiement affichée
- [ ] Date de paiement affichée
- [ ] Date limite de paiement affichée (si solde > 0)

### **Réinscription**
- [ ] Note "ℹ️ Réinscription" affichée
- [ ] Année scolaire mentionnée
- [ ] Classe précédente affichée
- [ ] Mention "Mise à jour élève effectuée" en pied de page

---

## ✅ Résultat Final

### **Avant** ❌
- Reçu générique sans distinction
- Informations minimales
- Pas de détail des frais
- Pas d'info sur le parcours élève

### **Après** ✅
- ✅ **Titre dynamique** selon type d'inscription
- ✅ **Matricule** pour réinscriptions
- ✅ **Statut élève** avec badge coloré
- ✅ **Résultat année précédente** avec moyenne
- ✅ **Détail complet des frais** (base + optionnels)
- ✅ **Informations de paiement** complètes
- ✅ **Couleur dynamique** du solde
- ✅ **Note spéciale** pour réinscriptions
- ✅ **Classe précédente** mentionnée
- ✅ **Mention mise à jour** élève

**Le reçu est maintenant complet, informatif et professionnel ! 🎉**

---

**Date** : 9 octobre 2025  
**Version** : 6.0  
**Statut** : ✅ Production Ready
