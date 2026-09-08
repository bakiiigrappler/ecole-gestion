<?php

namespace App\Helpers;

use App\Models\SchoolSettings;

class SchoolHelper
{
    /**
     * Obtenir les paramètres de l'école
     */
    public static function getSettings()
    {
        return SchoolSettings::getSettings();
    }

    /**
     * Obtenir le nom de l'école
     * 
     * Retourne le nom du premier groupe actif (primaire puis secondaire)
     */
    public static function getName()
    {
        $settings = self::getSettings();
        
        // Retourner le premier nom disponible selon les niveaux actifs
        if ($settings->has_primary && $settings->primary_school_name) {
            return $settings->primary_school_name;
        }
        
        if ($settings->has_secondary && $settings->secondary_school_name) {
            return $settings->secondary_school_name;
        }
        
        // Fallback
        return $settings->primary_school_name ?: $settings->secondary_school_name ?: 'Établissement Scolaire';
    }

    /**
     * Obtenir le logo de l'école
     */
    public static function getLogo()
    {
        return self::getSettings()->logo_url;
    }

    /**
     * Obtenir le sceau de l'école
     */
    public static function getSeal()
    {
        return self::getSettings()->seal_url;
    }

    /**
     * Obtenir l'année scolaire
     */
    public static function getAcademicYear()
    {
        return self::getSettings()->academic_year;
    }

    /**
     * Calculer l'année scolaire actuelle selon le calendrier gabonais
     * Au Gabon, l'année scolaire commence en septembre et se termine en juin
     * Exemple: De septembre 2025 à juin 2026 = 2025-2026
     */
    public static function getCurrentAcademicYearName()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Si on est entre septembre (9) et décembre (12), l'année scolaire est année-actuelle à année-suivante
        if ($currentMonth >= 9) {
            return $currentYear . '-' . ($currentYear + 1);
        }
        
        // Si on est entre janvier (1) et août (8), l'année scolaire est année-précédente à année-actuelle
        return ($currentYear - 1) . '-' . $currentYear;
    }

    /**
     * Calculer les dates de début et fin de l'année scolaire actuelle
     */
    public static function getCurrentAcademicYearDates()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Si on est entre septembre et décembre
        if ($currentMonth >= 9) {
            return [
                'start_date' => $currentYear . '-09-01',
                'end_date' => ($currentYear + 1) . '-06-30',
            ];
        }
        
        // Si on est entre janvier et août
        return [
            'start_date' => ($currentYear - 1) . '-09-01',
            'end_date' => $currentYear . '-06-30',
        ];
    }

    /**
     * Obtenir le titre du directeur
     */
    public static function getPrincipalTitle()
    {
        return self::getSettings()->principal_title;
    }

    /**
     * Obtenir le nom du directeur
     */
    public static function getPrincipalName()
    {
        return self::getSettings()->principal_name;
    }

    /**
     * Obtenir les informations de contact
     */
    public static function getContactInfo()
    {
        $settings = self::getSettings();
        return [
            'phone' => $settings->school_phone,
            'email' => $settings->school_email,
            'website' => $settings->school_website,
            'bp' => $settings->school_bp,
            'address' => $settings->school_address,
        ];
    }

    /**
     * Obtenir le nom de l'école selon le cycle
     * 
     * @param string $cycle Le cycle (preprimaire, primaire, college, lycee)
     * @return string
     */
    public static function getSchoolNameByCycle($cycle)
    {
        return self::getSettings()->getSchoolNameByCycle($cycle);
    }

    /**
     * Obtenir le nom de l'école selon le niveau (Level model ou ID)
     * 
     * @param mixed $level Instance de Level ou ID de niveau
     * @return string
     */
    public static function getSchoolNameByLevel($level)
    {
        return self::getSettings()->getSchoolNameByLevel($level);
    }

    /**
     * Vérifier si un niveau est actif
     * 
     * @param string $cycle Le cycle à vérifier
     * @return bool
     */
    public static function isLevelActive($cycle)
    {
        return self::getSettings()->isLevelActive($cycle);
    }

    /**
     * Obtenir les niveaux actifs
     * 
     * @return array
     */
    public static function getActiveLevels()
    {
        return self::getSettings()->getActiveLevels();
    }
}
