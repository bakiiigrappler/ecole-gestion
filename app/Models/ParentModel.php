<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use App\Models\Concerns\GardeQuiSupprime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentModel extends Model
{
    /*
     * Suppression douce : effacer une fiche la met en corbeille au lieu de la
     * detruire. Le super administrateur peut l'y reprendre ou l'y detruire.
     */
    use AppartientAUnEtablissement, GardeQuiSupprime, SoftDeletes;

    protected $table = 'parents';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_2',
        'gender',
        'address',
        'profession',
        'workplace',
        'user_id'
    ];
    
    protected $casts = [
    ];
    
    // Relation many-to-many avec Student
    public function students()
    {
        // Le lien de parente et les autorisations vivent sur le pivot : ils
        // decrivent la relation a un enfant donne, pas la personne.
        return $this->belongsToMany(Student::class, 'student_parent', 'parent_id', 'student_id')
            ->withPivot(['relationship_type', 'is_primary_contact', 'lives_with_student', 'can_pickup'])
            ->withTimestamps();
    }

    /**
     * Nom complet du responsable.
     *
     * L'accesseur manquait : `$parent->full_name` renvoyait null partout ou il
     * etait employe — la fiche d'inscription affichait un responsable sans nom.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
