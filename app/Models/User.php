<?php

namespace App\Models;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Etablissement de rattachement.
     *
     * Volontairement sans filtre global : le scope interroge l'utilisateur
     * connecte, et resoudre cet utilisateur passe par le modele — les deux
     * s'appelleraient l'un l'autre a chaque requete. Le cloisonnement des
     * comptes se fait donc explicitement, dans les controleurs qui les listent.
     */
    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'matricule',
        // Identifiant de connexion au même titre que les deux précédents : un
        // parent n'a pas toujours d'adresse électronique.
        'telephone',
        'role',
        'school_id',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Vérifier si l'utilisateur est admin ou superadmin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    /**
     * Vérifier si l'utilisateur est superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Scope pour filtrer les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer par rôle
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Générer un matricule unique basé sur le nom et prénom
     */
    public static function generateMatricule(string $fullName): string
    {
        // Nettoyer et diviser le nom complet
        $nameParts = explode(' ', strtoupper(trim($fullName)));
        
        // Prendre les premières lettres
        $firstName = $nameParts[0] ?? 'USER';
        $lastName = end($nameParts);
        
        // Si c'est le même nom (pas de nom de famille), utiliser le prénom
        if ($firstName === $lastName && count($nameParts) === 1) {
            $lastName = $firstName;
        }
        
        // Générer le préfixe : 2 premières lettres du prénom + 2 premières du nom
        $prefix = substr($firstName, 0, 2) . substr($lastName, 0, 2);
        
        // Année actuelle (2 derniers chiffres)
        $year = date('y');
        
        // Générer un suffixe unique avec un compteur
        $baseMatricule = $prefix . $year;
        $counter = 1;
        
        do {
            $matricule = $baseMatricule . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $exists = self::where('matricule', $matricule)->exists();
            $counter++;
        } while ($exists && $counter <= 999);
        
        return $matricule;
    }

    /**
     * Obtenir la couleur du badge selon le rôle
     */
    public function getRoleBadgeClass(): string
    {
        return match($this->role) {
            'superadmin' => 'bg-danger',
            'admin' => 'bg-warning',
            'teacher' => 'bg-info',
            'secretary' => 'bg-success',
            'parent' => 'bg-primary',
            default => 'bg-secondary'
        };
    }

    /**
     * Obtenir l'icône selon le rôle
     */
    public function getRoleIcon(): string
    {
        return match($this->role) {
            'superadmin' => 'bi-crown-fill',
            'admin' => 'bi-shield-fill',
            'teacher' => 'bi-mortarboard-fill',
            'secretary' => 'bi-clipboard-fill',
            'parent' => 'bi-people-fill',
            default => 'bi-person-fill'
        };
    }
}
