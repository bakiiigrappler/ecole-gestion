# 📊 Guide - Dashboard Amélioré avec Statistiques et Graphiques

## 🎯 Objectif

Transformer le dashboard simple en un **tableau de bord complet** avec :
- ✅ Statistiques détaillées de toutes les données
- ✅ Graphiques interactifs (Chart.js)
- ✅ Tableaux de rapports
- ✅ Boutons pour générer des rapports PDF/Excel

---

## 📋 Nouvelles Fonctionnalités

### **1. Statistiques Principales (4 Cartes)**

#### **Carte 1 : Total Élèves**
- Total des élèves inscrits
- Nombre d'élèves actifs
- Couleur : Bleu

#### **Carte 2 : Inscriptions Année en Cours**
- Total des inscriptions pour l'année
- Nombre de réinscriptions
- Couleur : Vert

#### **Carte 3 : Revenus Année**
- Revenus collectés pour l'année
- Taux de collection (%)
- Couleur : Orange

#### **Carte 4 : Solde à Percevoir**
- Montant restant à collecter
- Nombre d'impayés (partiels + non payés)
- Couleur : Rouge

---

### **2. Graphiques Interactifs**

#### **Graphique 1 : Inscriptions par Cycle** (Doughnut)
- Préprimaire
- Primaire
- Collège
- Lycée
- **Affichage** : Pourcentages au survol

#### **Graphique 2 : Statut des Élèves** (Bar)
- Nouveau
- Passant
- Redoublant
- **Affichage** : Barres colorées

#### **Graphique 3 : Répartition par Genre** (Pie)
- Garçons
- Filles
- **Affichage** : Pourcentages

#### **Graphique 4 : Évolution des Inscriptions** (Line)
- 12 derniers mois
- Tendance avec courbe lissée
- **Affichage** : Points cliquables

---

### **3. Statistiques Financières Détaillées**

```
┌─────────────────────────────────┐
│ FINANCES                        │
├─────────────────────────────────┤
│ Total attendu : 10,000,000 FCFA │
│ Collecté : 8,500,000 FCFA       │
│ Reste : 1,500,000 FCFA          │
│ [████████████░░░░] 85%          │
├─────────────────────────────────┤
│ ✓ Payé complet : 45             │
│ ⚠ Paiement partiel : 12         │
│ ✗ Non payé : 3                  │
└─────────────────────────────────┘
```

---

### **4. Tableaux de Rapports**

#### **Inscriptions Récentes**
| Élève | Classe | Statut | Date |
|-------|--------|--------|------|
| Jean PASSANT | CE1 A | Passant | 09/10/24 |
| Marie NOUVEAU | CP B | Nouveau | 08/10/24 |

#### **Paiements Récents**
| Élève | Montant | Reçu | Date |
|-------|---------|------|------|
| Jean PASSANT | 205,000 FCFA | REC20241000001 | 09/10/24 |
| Marie NOUVEAU | 205,000 FCFA | REC20241000002 | 08/10/24 |

#### **Top 5 Classes**
| # | Classe | Niveau | Cycle | Effectif | Capacité | Taux |
|---|--------|--------|-------|----------|----------|------|
| 🏆 | CE1 A | CE1 | Primaire | 30 | 30 | 100% |
| 🥈 | CP B | CP | Primaire | 28 | 30 | 93% |
| 🥉 | CM1 A | CM1 | Primaire | 27 | 30 | 90% |

---

### **5. Boutons de Génération de Rapports**

Menu déroulant "Rapports" avec :
- 📄 **Rapport général (PDF)** : Vue d'ensemble complète
- 💰 **Rapport financier (PDF)** : Détails des paiements
- 📋 **Rapport inscriptions (PDF)** : Liste des inscriptions
- 📊 **Exporter en Excel** : Données brutes

---

## 💻 Code Technique

### **1. Contrôleur** (`DashboardController.php`)

#### **Statistiques Collectées**

```php
// Année scolaire en cours
$currentYear = AcademicYear::where('is_current', true)->first();

// Statistiques élèves
$studentStats = [
    'total' => Student::count(),
    'actifs' => Student::currentlyActive()->count(),
    'anciens' => Student::formerStudents()->count(),
    'avec_redoublement' => Student::where('total_redoublements', '>', 0)->count(),
    'garcons' => Student::where('gender', 'male')->count(),
    'filles' => Student::where('gender', 'female')->count(),
];

// Statistiques inscriptions année en cours
$enrollmentStats = [
    'total' => Enrollment::whereHas('academicYear', fn($q) => $q->where('is_current', true))->count(),
    'nouveau' => Enrollment::where('student_status', 'nouveau')->whereHas(...)->count(),
    'passant' => Enrollment::where('student_status', 'passant')->whereHas(...)->count(),
    'redoublant' => Enrollment::where('student_status', 'redoublant')->whereHas(...)->count(),
    'nouvelle_inscription' => Enrollment::where('is_new_enrollment', true)->whereHas(...)->count(),
    'reinscription' => Enrollment::where('is_reinscription', true)->whereHas(...)->count(),
];

// Statistiques par cycle
$enrollmentsByCycle = [
    'preprimaire' => Enrollment::whereHas('schoolClass.level', fn($q) => $q->where('cycle', 'preprimaire'))->whereHas(...)->count(),
    'primaire' => ...,
    'college' => ...,
    'lycee' => ...,
];

// Statistiques financières
$financialStats = [
    'monthly_revenue' => Enrollment::whereMonth(...)->sum('amount_paid'),
    'yearly_revenue' => Enrollment::whereHas(...)->sum('amount_paid'),
    'total_fees' => Enrollment::whereHas(...)->sum('total_fees'),
    'balance_due' => Enrollment::whereHas(...)->sum('balance_due'),
    'paid_count' => Enrollment::where('payment_status', 'paid')->whereHas(...)->count(),
    'partial_count' => Enrollment::where('payment_status', 'partial')->whereHas(...)->count(),
    'unpaid_count' => Enrollment::where('payment_status', 'unpaid')->whereHas(...)->count(),
    'collection_rate' => round(($yearly_revenue / $total_fees) * 100, 1),
];

// Top 5 classes
$classesByEnrollment = SchoolClass::withCount(['enrollments' => fn($q) => $q->whereHas(...)])
    ->orderBy('enrollments_count', 'desc')
    ->take(5)
    ->get();

// Évolution des inscriptions (12 mois)
$enrollmentTrend = [];
for ($i = 11; $i >= 0; $i--) {
    $date = now()->subMonths($i);
    $enrollmentTrend[] = [
        'month' => $date->format('M Y'),
        'count' => Enrollment::whereMonth(...)->whereYear(...)->count()
    ];
}

// Activités récentes
$recentEnrollments = Enrollment::with(['schoolClass', 'academicYear'])
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

$recentPayments = Enrollment::where('payment_status', 'paid')
    ->orderBy('updated_at', 'desc')
    ->take(5)
    ->get();
```

---

### **2. Vue** (`dashboard/index.blade.php`)

#### **Structure**

```blade
<!-- En-tête avec bouton Rapports -->
<div class="d-flex justify-content-between">
    <h1>Tableau de bord</h1>
    <div>
        <span class="badge">{{ $currentYear->name }}</span>
        <div class="btn-group">
            <button class="dropdown-toggle">Rapports</button>
            <ul class="dropdown-menu">
                <li><a onclick="generateGeneralReport()">Rapport général</a></li>
                <li><a onclick="generateFinancialReport()">Rapport financier</a></li>
                <li><a onclick="generateEnrollmentReport()">Rapport inscriptions</a></li>
                <li><a href="#">Exporter en Excel</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- 4 Cartes de statistiques principales -->
<div class="row">
    <div class="col-xl-3"><!-- Total Élèves --></div>
    <div class="col-xl-3"><!-- Inscriptions --></div>
    <div class="col-xl-3"><!-- Revenus --></div>
    <div class="col-xl-3"><!-- Solde --></div>
</div>

<!-- 2 Graphiques : Cycle + Statut -->
<div class="row">
    <div class="col-lg-6">
        <canvas id="cycleChart"></canvas>
    </div>
    <div class="col-lg-6">
        <canvas id="statusChart"></canvas>
    </div>
</div>

<!-- Actions rapides -->
<div class="row">
    <!-- Boutons d'actions -->
</div>

<!-- Graphique évolution + Finances détaillées -->
<div class="row">
    <div class="col-lg-8">
        <canvas id="enrollmentTrendChart"></canvas>
    </div>
    <div class="col-lg-4">
        <!-- Statistiques financières détaillées -->
    </div>
</div>

<!-- Inscriptions récentes + Paiements récents -->
<div class="row">
    <div class="col-lg-6">
        <!-- Tableau inscriptions récentes -->
    </div>
    <div class="col-lg-6">
        <!-- Tableau paiements récents -->
    </div>
</div>

<!-- Genre + Top 5 Classes -->
<div class="row">
    <div class="col-lg-4">
        <canvas id="genderChart"></canvas>
    </div>
    <div class="col-lg-8">
        <!-- Tableau Top 5 classes -->
    </div>
</div>
```

---

### **3. Scripts Chart.js**

```javascript
// Charger Chart.js depuis CDN
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

// Données PHP → JavaScript
const cycleData = @json($enrollmentsByCycle);
const statusData = @json($enrollmentStats);
const genderData = @json($studentStats);
const trendData = @json($enrollmentTrend);

// Créer les graphiques
new Chart(document.getElementById('cycleChart'), {
    type: 'doughnut',
    data: {
        labels: ['Préprimaire', 'Primaire', 'Collège', 'Lycée'],
        datasets: [{
            data: [cycleData.preprimaire, cycleData.primaire, cycleData.college, cycleData.lycee],
            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${context.label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
```

---

## 📊 Données Affichées

### **Statistiques Élèves**
- Total élèves
- Élèves actifs
- Élèves anciens
- Avec redoublement
- Garçons / Filles

### **Statistiques Inscriptions**
- Total inscriptions année en cours
- Nouveaux
- Passants
- Redoublants
- Nouvelles inscriptions
- Réinscriptions

### **Statistiques par Cycle**
- Préprimaire
- Primaire
- Collège
- Lycée

### **Statistiques Financières**
- Revenus mensuels
- Revenus annuels
- Total frais attendus
- Solde à percevoir
- Taux de collection
- Nombre payé complet
- Nombre paiement partiel
- Nombre non payé

### **Top 5 Classes**
- Nom de la classe
- Niveau
- Cycle
- Effectif actuel
- Capacité maximale
- Taux de remplissage

### **Évolution**
- Inscriptions sur 12 mois
- Tendance graphique

### **Activités Récentes**
- 5 dernières inscriptions
- 5 derniers paiements

---

## 🎨 Design

### **Couleurs des Cartes**

| Carte | Couleur | Dégradé |
|-------|---------|---------|
| Total Élèves | Bleu | #3498db → #2980b9 |
| Inscriptions | Vert | #27ae60 → #2ecc71 |
| Revenus | Orange | #f39c12 → #e67e22 |
| Solde | Rouge | #e74c3c → #c0392b |

### **Types de Graphiques**

| Graphique | Type | Couleurs |
|-----------|------|----------|
| Par Cycle | Doughnut | Rose, Bleu, Jaune, Turquoise |
| Par Statut | Bar | Bleu, Vert, Jaune |
| Par Genre | Pie | Bleu, Rouge |
| Évolution | Line | Bleu avec remplissage |

---

## 🔧 Modifications Techniques

### **Contrôleur**

**Avant** :
```php
public function index()
{
    $totalStudents = Student::count();
    $totalTeachers = Teacher::where('status', 'active')->count();
    $totalClasses = SchoolClass::where('is_active', true)->count();
    $monthlyRevenue = Payment::whereMonth(...)->sum('amount');
    
    return view('dashboard.index', compact(...));
}
```

**Après** :
```php
public function index()
{
    // 10+ sections de statistiques
    $studentStats = [...];
    $enrollmentStats = [...];
    $enrollmentsByCycle = [...];
    $financialStats = [...];
    $classesByEnrollment = [...];
    $enrollmentTrend = [...];
    $gradeStats = [...];
    $recentEnrollments = [...];
    $recentPayments = [...];
    
    return view('dashboard.index', compact(...));
}
```

### **Vue**

**Avant** :
- 4 cartes simples
- Liste d'activités statiques
- Tableau de classes statique
- Pas de graphiques

**Après** :
- 4 cartes avec vraies données
- 4 graphiques Chart.js
- 3 tableaux dynamiques
- Boutons de rapports
- Statistiques financières détaillées

---

## 📈 Graphiques Chart.js

### **Configuration Commune**

```javascript
{
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    // Personnalisation du tooltip
                }
            }
        }
    }
}
```

### **Doughnut Chart (Cycle)**

```javascript
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Préprimaire', 'Primaire', 'Collège', 'Lycée'],
        datasets: [{
            data: [10, 45, 20, 15],
            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
        }]
    }
});
```

### **Bar Chart (Statut)**

```javascript
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Nouveau', 'Passant', 'Redoublant'],
        datasets: [{
            data: [25, 40, 10],
            backgroundColor: ['#3498db', '#2ecc71', '#f1c40f']
        }]
    }
});
```

### **Line Chart (Évolution)**

```javascript
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan 24', 'Fév 24', ..., 'Déc 24'],
        datasets: [{
            data: [5, 8, 12, 15, ...],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            fill: true,
            tension: 0.4
        }]
    }
});
```

---

## 🎯 Résultat Final

### **Avant** ❌

```
Dashboard Simple
├── 4 cartes (données statiques)
├── Actions rapides
├── Activités récentes (statiques)
├── Présences (statique)
└── Événements (statiques)
```

**Problèmes** :
- Données statiques
- Pas de graphiques
- Pas de rapports
- Vue limitée

### **Après** ✅

```
Dashboard Complet
├── En-tête avec bouton Rapports
├── 4 cartes de statistiques (vraies données)
│   ├── Total Élèves (+ actifs)
│   ├── Inscriptions année (+ réinscriptions)
│   ├── Revenus année (+ taux collection)
│   └── Solde à percevoir (+ impayés)
├── 4 Graphiques interactifs
│   ├── Inscriptions par Cycle (Doughnut)
│   ├── Statut des Élèves (Bar)
│   ├── Répartition par Genre (Pie)
│   └── Évolution 12 mois (Line)
├── Actions rapides
├── Statistiques financières détaillées
│   ├── Total / Collecté / Reste
│   ├── Barre de progression
│   └── Détail par statut paiement
├── 3 Tableaux de rapports
│   ├── Inscriptions récentes (5)
│   ├── Paiements récents (5)
│   └── Top 5 Classes (avec trophées)
└── Boutons de génération de rapports
    ├── Rapport général (PDF)
    ├── Rapport financier (PDF)
    ├── Rapport inscriptions (PDF)
    └── Export Excel
```

**Améliorations** :
- ✅ Données réelles de la base de données
- ✅ 4 graphiques interactifs
- ✅ Statistiques complètes
- ✅ Tableaux détaillés
- ✅ Boutons de rapports
- ✅ Design moderne et professionnel
- ✅ Vue d'ensemble complète

---

## 📋 Checklist de Validation

### **Contrôleur**
- [x] Statistiques élèves
- [x] Statistiques inscriptions
- [x] Statistiques par cycle
- [x] Statistiques financières
- [x] Top 5 classes
- [x] Évolution 12 mois
- [x] Activités récentes

### **Vue**
- [x] 4 cartes principales
- [x] 4 graphiques Chart.js
- [x] Bouton rapports
- [x] Statistiques financières détaillées
- [x] Tableau inscriptions récentes
- [x] Tableau paiements récents
- [x] Tableau top 5 classes

### **Graphiques**
- [x] Graphique par cycle (doughnut)
- [x] Graphique par statut (bar)
- [x] Graphique par genre (pie)
- [x] Graphique évolution (line)
- [x] Tooltips personnalisés
- [x] Légendes positionnées
- [x] Responsive

### **Fonctionnalités**
- [x] Données dynamiques
- [x] Boutons de rapports (placeholders)
- [x] Liens vers pages détaillées
- [x] Badges colorés
- [x] Icônes appropriées

---

**Date** : 10 octobre 2025  
**Version** : 10.0  
**Statut** : ✅ Dashboard Complet et Fonctionnel
