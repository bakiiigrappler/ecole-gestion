<?php

namespace App\Models\Concerns;

/**
 * Retient qui a mis une fiche à la corbeille.
 *
 * Une corbeille qui ne dit pas d'où vient la suppression n'aide qu'à moitié :
 * devant vingt élèves effacés, la première question est « par qui ? ». La
 * colonne `deleted_by` se remplit donc toute seule, au moment du geste.
 *
 * S'utilise avec `SoftDeletes` : sans lui, la suppression est définitive et il
 * n'y a rien à tracer.
 */
trait GardeQuiSupprime
{
    public static function bootGardeQuiSupprime(): void
    {
        static::deleting(function ($modele) {
            // La suppression définitive n'écrit rien : la ligne s'en va.
            if ($modele->isForceDeleting() || ! auth()->check()) {
                return;
            }

            $modele->deleted_by = auth()->id();
            $modele->saveQuietly();
        });

        static::restoring(function ($modele) {
            $modele->deleted_by = null;
        });
    }
}
