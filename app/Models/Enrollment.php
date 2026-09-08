<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'enrollment_date',
        'status',
        'notes',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_date_of_birth',
        'applicant_gender',
        'applicant_phone',
        'applicant_email',
        'applicant_address',
        // Les cinq colonnes du responsable existaient en base mais etaient
        // absentes d'ici : rien ne pouvait les ecrire, et la fiche affichait
        // toujours un responsable vide.
        'parent_first_name',
        'parent_last_name',
        'parent_phone',
        'parent_email',
        'parent_relationship',
        'enrollment_status',
        'is_new_enrollment',
        'is_reinscription',
        'reinscription_student_id',
        'student_status',
        'previous_class_id',
        'previous_academic_year_id',
        'previous_year_result',
        'previous_year_average',
        'status_comments',
        'total_fees',
        'amount_paid',
        'balance_due',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'payment_status',
        'payment_due_date',
        'receipt_number',
        'enrollment_code',
        'mobile_money_provider',
        'mobile_money_number'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'applicant_date_of_birth' => 'date',
        'is_new_enrollment' => 'boolean',
        'is_reinscription' => 'boolean',
        'payment_due_date' => 'date',
        'total_fees' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'previous_year_average' => 'decimal:2',
    ];

    /**
     * Get the student that owns the enrollment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the class for this enrollment.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the academic year for this enrollment.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the online payments for this enrollment.
     */
    public function onlinePayments(): HasMany
    {
        return $this->hasMany(OnlinePayment::class);
    }

    /**
     * Get the payments for this enrollment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec les frais d'inscription
     */
    public function enrollmentFees(): HasMany
    {
        return $this->hasMany(EnrollmentFee::class);
    }

    /**
     * Get the parent through the student relationship.
     * This is not a true Eloquent relationship, just a helper method.
     */
    public function getParent()
    {
        if (!$this->student) {
            return null;
        }
        
        return $this->student->parents()->first();
    }

    /**
     * Get the parent ID through the student relationship.
     */
    public function getParentIdAttribute()
    {
        $parent = $this->getParent();
        return $parent ? $parent->id : null;
    }

    // Accesseurs
    public function getStatusBadgeAttribute()
    {
        return [
            'active' => '<span class="badge bg-success">Actif</span>',
            'inactive' => '<span class="badge bg-secondary">Inactif</span>',
            'transferred' => '<span class="badge bg-warning">Transféré</span>',
            'graduated' => '<span class="badge bg-primary">Diplômé</span>'
        ][$this->status] ?? '<span class="badge bg-light">Inconnu</span>';
    }

    public function getStatusLabelAttribute()
    {
        return [
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'transferred' => 'Transféré',
            'graduated' => 'Diplômé'
        ][$this->status] ?? 'Inconnu';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereHas('academicYear', function($q) {
            $q->where('is_current', true);
        });
    }

    public function scopeByCycle($query, $cycle)
    {
        return $query->whereHas('schoolClass.level', function($q) use ($cycle) {
            $q->where('cycle', $cycle);
        });
    }

    public function scopeByAcademicYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    // Méthodes utiles
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function canBeModified()
    {
        return in_array($this->status, ['active', 'inactive']);
    }

    public function getDurationInDays()
    {
        $startDate = $this->enrollment_date;
        $endDate = $this->academicYear->end_date ?? now();
        
        return $startDate->diffInDays($endDate);
    }

    public function getFormattedEnrollmentDate()
    {
        return $this->enrollment_date->format('d/m/Y');
    }

    // Accesseurs pour le nouveau workflow
    /**
     * Identite portee par le dossier.
     *
     * Une inscription deposee au guichet renseigne les champs `applicant_*` ;
     * une inscription rattachee a un eleve existant les laisse vides et
     * l'identite vit sur l'eleve. Les recus ne lisaient que les premiers et
     * tombaient sur `format() on null` pour les 724 dossiers rattaches.
     */
    public function getApplicantFullNameAttribute()
    {
        $nom = trim($this->applicant_first_name . ' ' . $this->applicant_last_name);

        return $nom !== '' ? $nom : ($this->student?->full_name ?? '');
    }

    /** Date de naissance du dossier, reprise sur l'eleve a defaut. */
    public function getIdentiteNaissanceAttribute()
    {
        return $this->applicant_date_of_birth ?? $this->student?->date_of_birth;
    }

    /** Sexe du dossier, repris sur l'eleve a defaut. */
    public function getIdentiteSexeAttribute()
    {
        return $this->applicant_gender ?? $this->student?->gender;
    }

    /** Adresse du dossier, reprise sur l'eleve a defaut. */
    public function getIdentiteAdresseAttribute()
    {
        return $this->applicant_address ?: $this->student?->address;
    }

    /** Telephone du dossier, repris sur l'eleve a defaut. */
    public function getIdentiteTelephoneAttribute()
    {
        return $this->applicant_phone ?: $this->student?->phone;
    }

    /** Courriel du dossier, repris sur l'eleve a defaut. */
    public function getIdentiteCourrielAttribute()
    {
        return $this->applicant_email ?: $this->student?->email;
    }

    public function getParentFullNameAttribute()
    {
        return $this->parent_first_name . ' ' . $this->parent_last_name;
    }

    public function getApplicantAgeAttribute()
    {
        return $this->identite_naissance?->age;
    }

    public function getEnrollmentStatusBadgeAttribute()
    {
        return [
            'pending' => '<span class="badge bg-warning">En attente</span>',
            'student_created' => '<span class="badge bg-info">Élève créé</span>',
            'active' => '<span class="badge bg-success">Actif</span>',
            'inactive' => '<span class="badge bg-secondary">Inactif</span>',
            'transferred' => '<span class="badge bg-warning">Transféré</span>',
            'graduated' => '<span class="badge bg-primary">Diplômé</span>'
        ][$this->enrollment_status] ?? '<span class="badge bg-light">Inconnu</span>';
    }

    public function getParentRelationshipLabelAttribute()
    {
        return [
            'father' => 'Père',
            'mother' => 'Mère',
            'guardian' => 'Tuteur',
            'other' => 'Autre'
        ][$this->parent_relationship] ?? 'Non spécifié';
    }

    // Scopes pour le nouveau workflow
    public function scopePendingStudentCreation($query)
    {
        return $query->where('enrollment_status', 'pending')->whereNull('student_id');
    }

    public function scopeWithStudentCreated($query)
    {
        return $query->where('enrollment_status', 'student_created')->whereNotNull('student_id');
    }

    public function scopeNewEnrollments($query)
    {
        return $query->where('is_new_enrollment', true);
    }

    // Méthodes utiles pour le workflow
    public function isPendingStudentCreation()
    {
        return $this->enrollment_status === 'pending' && is_null($this->student_id);
    }

    public function hasStudentCreated()
    {
        return $this->enrollment_status === 'student_created' && !is_null($this->student_id);
    }

    public function canCreateStudent()
    {
        return $this->isPendingStudentCreation();
    }

    public function markAsStudentCreated($studentId)
    {
        $this->update([
            'student_id' => $studentId,
            'enrollment_status' => 'student_created',
            'status' => 'active'
        ]);
    }

    public function getStudentDataForCreation()
    {
        return [
            'first_name' => $this->applicant_first_name,
            'last_name' => $this->applicant_last_name,
            'date_of_birth' => $this->applicant_date_of_birth,
            'gender' => $this->applicant_gender,
            'address' => $this->applicant_address,
            'enrollment_date' => $this->enrollment_date,
            'status' => 'active'
        ];
    }

    public function getParentDataForCreation()
    {
        // Vérifier si les données parent sont disponibles
        if (!$this->parent_first_name || !$this->parent_last_name || !$this->parent_phone) {
            return null; // Pas de données parent disponibles
        }
        
        return [
            'first_name' => $this->parent_first_name,
            'last_name' => $this->parent_last_name,
            'phone' => $this->parent_phone,
            'email' => $this->parent_email,
            'relationship_type' => $this->parent_relationship,
            'address' => $this->applicant_address ?? 'Adresse non renseignée', // Utiliser l'adresse de l'inscrit ou valeur par défaut
            'is_primary_contact' => true,
            'can_pickup' => true
        ];
    }

    // Méthodes pour la gestion des paiements
    public function getPaymentStatusBadgeAttribute()
    {
        return [
            'pending' => '<span class="badge bg-warning">En attente</span>',
            'partial' => '<span class="badge bg-info">Partiel</span>',
            'completed' => '<span class="badge bg-success">Complet</span>',
            'overdue' => '<span class="badge bg-danger">En retard</span>'
        ][$this->payment_status] ?? '<span class="badge bg-light">Inconnu</span>';
    }

    public function getPaymentMethodLabelAttribute()
    {
        return [
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'check' => 'Chèque',
            'mobile_money' => 'Mobile Money',
            'other' => 'Autre'
        ][$this->payment_method] ?? 'Non spécifié';
    }

    public function getFormattedTotalFeesAttribute()
    {
        return number_format($this->total_fees, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedAmountPaidAttribute()
    {
        return number_format($this->amount_paid, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedBalanceDueAttribute()
    {
        return number_format($this->balance_due, 0, ',', ' ') . ' FCFA';
    }

    // Méthodes de calcul
    public function calculateBalance()
    {
        $this->balance_due = $this->total_fees - $this->amount_paid;
        $this->updatePaymentStatus();
        return $this->balance_due;
    }

    public function updatePaymentStatus()
    {
        if ($this->amount_paid == 0) {
            $this->payment_status = 'pending';
        } elseif ($this->amount_paid < $this->total_fees) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'completed';
        }

        // Vérifier si en retard
        if ($this->payment_due_date && $this->payment_due_date->isPast() && $this->payment_status !== 'completed') {
            $this->payment_status = 'overdue';
        }
    }

    public function generateReceiptNumber()
    {
        if (!$this->receipt_number) {
            $year = date('Y');
            $month = date('m');
            
            $lastReceipt = static::where('receipt_number', 'like', "REC{$year}{$month}%")
                                ->orderBy('receipt_number', 'desc')
                                ->first();
            
            $nextNumber = 1;
            if ($lastReceipt) {
                $lastNumber = intval(substr($lastReceipt->receipt_number, -4));
                $nextNumber = $lastNumber + 1;
            }
            
            $this->receipt_number = "REC{$year}{$month}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $this->save();
        }
        
        return $this->receipt_number;
    }

    /**
     * Générer un code d'inscription unique pour le QR code
     */
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

    /**
     * Générer automatiquement une référence de paiement
     */
    public function generatePaymentReference()
    {
        if (!$this->payment_reference) {
            $year = date('Y');
            $month = date('m');
            $day = date('d');
            
            // Format: PAY + YYYYMMDD + 4 chiffres
            $lastPayment = static::where('payment_reference', 'like', "PAY{$year}{$month}{$day}%")
                                ->orderBy('payment_reference', 'desc')
                                ->first();
            
            $nextNumber = 1;
            if ($lastPayment) {
                $lastNumber = intval(substr($lastPayment->payment_reference, -4));
                $nextNumber = $lastNumber + 1;
            }
            
            $this->payment_reference = "PAY{$year}{$month}{$day}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $this->save();
        }
        
        return $this->payment_reference;
    }

    public function isFullyPaid()
    {
        return $this->payment_status === 'completed';
    }

    public function hasPartialPayment()
    {
        return $this->payment_status === 'partial';
    }

    public function isOverdue()
    {
        return $this->payment_status === 'overdue';
    }

    // Scopes pour les paiements
    public function scopeFullyPaid($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue');
    }

    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }
    
    // Méthodes pour la gestion du statut de réinscription
    
    /**
     * Déterminer automatiquement le statut de l'élève basé sur son historique
     */
    public function determineStudentStatus()
    {
        // Si ce n'est pas une réinscription, c'est un nouvel élève
        if (!$this->is_reinscription || !$this->reinscription_student_id) {
            $this->student_status = 'nouveau';
            $this->previous_year_result = 'non_applicable';
            return 'nouveau';
        }
        
        // Trouver l'élève par son matricule
        $student = Student::where('student_id', $this->reinscription_student_id)->first();
        
        if (!$student) {
            $this->student_status = 'nouveau';
            $this->status_comments = 'Matricule non trouvé - traité comme nouvel élève';
            return 'nouveau';
        }
        
        // Trouver la dernière inscription de l'élève
        $lastEnrollment = Enrollment::where('student_id', $student->id)
            ->where('id', '!=', $this->id)
            ->orderBy('academic_year_id', 'desc')
            ->with(['schoolClass.level', 'academicYear'])
            ->first();
        
        if (!$lastEnrollment) {
            $this->student_status = 'nouveau';
            $this->status_comments = 'Aucune inscription précédente trouvée';
            return 'nouveau';
        }
        
        // Sauvegarder les informations de l'année précédente
        $this->previous_class_id = $lastEnrollment->class_id;
        $this->previous_academic_year_id = $lastEnrollment->academic_year_id;
        
        // Calculer la moyenne de l'année précédente
        $previousAverage = $this->calculatePreviousYearAverage($student->id, $lastEnrollment->academic_year_id);
        $this->previous_year_average = $previousAverage;
        
        // Déterminer si l'élève passe ou redouble (moyenne >= 10 pour passer)
        $hasPassed = $previousAverage >= 10;
        
        if ($hasPassed) {
            $this->previous_year_result = 'admis';
            
            // Vérifier si l'élève s'inscrit dans la classe suivante
            $isNextClass = $this->isNextClass($lastEnrollment->schoolClass, $this->schoolClass);
            
            if ($isNextClass) {
                $this->student_status = 'passant';
                $this->status_comments = "Admis avec moyenne de {$previousAverage}/20 - Passage en classe supérieure";
            } else {
                $this->student_status = 'nouveau';
                $this->status_comments = "Admis mais inscription dans une classe différente";
            }
        } else {
            $this->previous_year_result = 'redouble';
            $this->student_status = 'redoublant';
            $this->status_comments = "Moyenne insuffisante ({$previousAverage}/20) - Redoublement";
        }
        
        return $this->student_status;
    }
    
    /**
     * Calculer la moyenne générale de l'année précédente
     */
    private function calculatePreviousYearAverage($studentId, $academicYearId)
    {
        $grades = \App\Models\Grade::where('student_id', $studentId)
            ->whereHas('subject', function($q) use ($academicYearId) {
                $q->whereHas('schedules', function($sq) use ($academicYearId) {
                    $sq->where('academic_year_id', $academicYearId);
                });
            })
            ->get();
        
        if ($grades->isEmpty()) {
            return 0;
        }
        
        $totalWeightedScore = 0;
        $totalCoefficients = 0;
        
        foreach ($grades as $grade) {
            $subject = $grade->subject;
            $coefficient = $subject ? ($subject->coefficient ?? 1) : 1;
            
            $totalWeightedScore += $grade->score * $coefficient;
            $totalCoefficients += $coefficient;
        }
        
        return $totalCoefficients > 0 ? round($totalWeightedScore / $totalCoefficients, 2) : 0;
    }
    
    /**
     * Vérifier si la classe actuelle est la classe suivante de la classe précédente
     */
    private function isNextClass($previousClass, $currentClass)
    {
        if (!$previousClass || !$currentClass) {
            return false;
        }
        
        // Charger les niveaux si nécessaire
        if (!$previousClass->relationLoaded('level')) {
            $previousClass->load('level');
        }
        if (!$currentClass->relationLoaded('level')) {
            $currentClass->load('level');
        }
        
        $previousLevel = $previousClass->level;
        $currentLevel = $currentClass->level;
        
        if (!$previousLevel || !$currentLevel) {
            return false;
        }
        
        // Vérifier si le cycle est le même
        if ($previousLevel->cycle !== $currentLevel->cycle) {
            // Peut-être un passage de cycle (ex: préprimaire -> primaire)
            return false;
        }
        
        // Vérifier si l'ordre du niveau actuel est supérieur de 1
        return ($currentLevel->order == $previousLevel->order + 1);
    }
    
    /**
     * Obtenir le badge de statut de l'élève
     */
    public function getStudentStatusBadgeAttribute()
    {
        return [
            'nouveau' => '<span class="badge bg-success">Nouveau</span>',
            'redoublant' => '<span class="badge bg-warning">Redoublant</span>',
            'passant' => '<span class="badge bg-primary">Passant</span>'
        ][$this->student_status] ?? '<span class="badge bg-secondary">Non défini</span>';
    }
    
    /**
     * Obtenir le label du statut de l'élève
     */
    public function getStudentStatusLabelAttribute()
    {
        return [
            'nouveau' => 'Nouveau',
            'redoublant' => 'Redoublant',
            'passant' => 'Passant'
        ][$this->student_status] ?? 'Non défini';
    }
    
    /**
     * Scope pour les élèves redoublants
     */
    public function scopeRedoublants($query)
    {
        return $query->where('student_status', 'redoublant');
    }
    
    /**
     * Scope pour les élèves passants
     */
    public function scopePassants($query)
    {
        return $query->where('student_status', 'passant');
    }
    
    /**
     * Scope pour les nouveaux élèves
     */
    public function scopeNouveaux($query)
    {
        return $query->where('student_status', 'nouveau');
    }
}
