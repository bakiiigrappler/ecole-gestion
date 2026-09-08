@extends('layouts.app')

@section('title', 'Autorisation d\'Entrée')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-qr-code me-2"></i>
                        Génération de l'Autorisation d'Entrée
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="text-muted">Génération de l'autorisation d'entrée en cours...</p>
                    <p class="text-muted small">Le téléchargement va démarrer automatiquement.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteneur caché pour la génération -->
    <div id="authorizationContent" style="position: absolute; left: -9999px; width: 210mm; height: 148mm;">
        <!-- Le contenu sera généré ici -->
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// Données de l'inscription
const enrollmentData = {
    id: {{ $enrollment->id }},
    enrollment_code: '{{ $enrollment->enrollment_code }}',
    student_name: '{{ $enrollment->applicant_first_name }} {{ $enrollment->applicant_last_name }}',
    student_id: '{{ $enrollment->student ? $enrollment->student->student_id : 'N/A' }}',
    date_of_birth: '{{ $enrollment->identite_naissance?->format('d/m/Y') ?? 'N/A' }}',
    gender: '{{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }}',
    class_name: '{{ $enrollment->schoolClass->name ?? 'N/A' }}',
    cycle: '{{ ucfirst($enrollment->schoolClass->getSafeCycle() ?? 'N/A') }}',
    student_status: '{{ $enrollment->student_status }}',
    is_reinscription: {{ $enrollment->is_reinscription ? 'true' : 'false' }},
    enrollment_date: '{{ $enrollment->enrollment_date?->format('d/m/Y') ?? '—' }}',
    receipt_number: '{{ $enrollment->receipt_number }}',
    academic_year: '{{ $enrollment->academicYear->name ?? 'N/A' }}',
    payment_status: '{{ $enrollment->payment_status }}'
};

const schoolSettings = {
    name: '{{ $schoolName ?? 'Établissement Scolaire' }}',
    type: 'Établissement Scolaire',
    city: '{{ $schoolSettings->city ?? 'Libreville' }}',
    country: '{{ $schoolSettings->country ?? 'Gabon' }}',
    phone: '{{ $schoolSettings->school_phone ?? '+241 XX XX XX XX' }}',
    email: '{{ $schoolSettings->school_email ?? 'contact@ecole.ga' }}',
    logo: '{{ $schoolSettings && $schoolSettings->school_logo ? asset('storage/' . $schoolSettings->school_logo) : '' }}'
};

// Fonction pour charger une image et la convertir en data URL
function loadImageAsDataURL(url) {
    return new Promise((resolve, reject) => {
        if (!url) {
            resolve(null);
            return;
        }
        
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = function() {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                const dataURL = canvas.toDataURL('image/png');
                resolve(dataURL);
            } catch (e) {
                console.error('Erreur lors de la conversion de l\'image:', e);
                resolve(null);
            }
        };
        
        img.onerror = function() {
            console.error('Erreur lors du chargement de l\'image:', url);
            resolve(null);
        };
        
        img.src = url;
    });
}

// Fonction pour générer le PDF
async function generateAuthorizationPDF() {
    try {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('l', 'mm', 'a5'); // A5 paysage (210 x 148 mm)
        
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 10;
        
        // Charger le logo de l'école
        let logoData = null;
        if (schoolSettings.logo) {
            logoData = await loadImageAsDataURL(schoolSettings.logo);
        }
        
        // Charger le QR code depuis l'API
        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(enrollmentData.enrollment_code)}`;
        const qrCodeData = await loadImageAsDataURL(qrCodeUrl);
        
        // En-tête compact avec logo et informations sur une ligne
        let yPos = margin;
        
        // Logo à gauche (plus petit)
        if (logoData) {
            pdf.addImage(logoData, 'PNG', margin, yPos, 12, 12);
        }
        
        // Nom de l'école et informations à droite du logo
        const textStartX = margin + 15;
        
        pdf.setFontSize(11);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(0, 123, 255);
        pdf.text(schoolSettings.name, textStartX, yPos + 4);
        
        // Informations école sur la même ligne (plus petit)
        pdf.setFontSize(6);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(100, 100, 100);
        pdf.text(`${schoolSettings.type} - ${schoolSettings.city}, ${schoolSettings.country} | Tél: ${schoolSettings.phone}`, textStartX, yPos + 9);
        
        yPos += 15;
        
        // Ligne de séparation
        pdf.setDrawColor(0, 123, 255);
        pdf.setLineWidth(0.5);
        pdf.line(margin, yPos, pageWidth - margin, yPos);
        yPos += 5;
        
        // Titre
        pdf.setFillColor(0, 123, 255);
        pdf.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(255, 255, 255);
        pdf.text(`AUTORISATION D'ENTRÉE`, pageWidth / 2, yPos + 7, { align: 'center' });
        yPos += 15;
        
        // Début du contenu en 2 colonnes
        const leftColX = margin;
        const leftColWidth = (pageWidth - 2 * margin) * 0.5;
        const rightColX = leftColX + leftColWidth + 10;
        const rightColWidth = (pageWidth - 2 * margin) * 0.4;
        
        const boxHeight = 50; // Hauteur des cadres
        
        // COLONNE GAUCHE - Informations essentielles de l'élève
        let leftY = yPos;
        
        // Cadre pour les informations
        pdf.setDrawColor(0, 123, 255);
        pdf.setLineWidth(0.3);
        pdf.rect(leftColX, leftY, leftColWidth, boxHeight);
        
        // Titre section
        pdf.setFillColor(248, 249, 250);
        pdf.rect(leftColX, leftY, leftColWidth, 8, 'F');
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(0, 123, 255);
        pdf.text('INFORMATIONS DE L\'ÉLÈVE', leftColX + 3, leftY + 5.5);
        leftY += 10;
        
        // Informations essentielles uniquement
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(0, 0, 0);
        
        const infoLeftX = leftColX + 3;
        const infoRightX = leftColX + leftColWidth - 3;
        
        // Nom complet
        pdf.setFont('helvetica', 'bold');
        pdf.text('Nom :', infoLeftX, leftY);
        pdf.setFont('helvetica', 'normal');
        pdf.text(enrollmentData.student_name, infoRightX, leftY, { align: 'right' });
        leftY += 6;
        
        // Matricule
        if (enrollmentData.student_id !== 'N/A') {
            pdf.setFont('helvetica', 'bold');
            pdf.text('Matricule :', infoLeftX, leftY);
            pdf.setFont('helvetica', 'normal');
            pdf.text(enrollmentData.student_id, infoRightX, leftY, { align: 'right' });
            leftY += 6;
        }
        
        // Classe
        pdf.setFont('helvetica', 'bold');
        pdf.text('Classe :', infoLeftX, leftY);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`${enrollmentData.class_name} (${enrollmentData.cycle})`, infoRightX, leftY, { align: 'right' });
        leftY += 6;
        
        // Date d'inscription
        pdf.setFont('helvetica', 'bold');
        pdf.text('Date inscription :', infoLeftX, leftY);
        pdf.setFont('helvetica', 'normal');
        pdf.text(enrollmentData.enrollment_date, infoRightX, leftY, { align: 'right' });
        leftY += 6;
        
        // Code d'inscription
        pdf.setFont('helvetica', 'bold');
        pdf.text('Code :', infoLeftX, leftY);
        pdf.setFont('helvetica', 'normal');
        pdf.text(enrollmentData.enrollment_code, infoRightX, leftY, { align: 'right' });
        
        // COLONNE DROITE - QR Code uniquement
        let rightY = yPos;
        
        // Cadre pour le QR code
        pdf.setDrawColor(0, 123, 255);
        pdf.setLineWidth(0.3);
        pdf.rect(rightColX, rightY, rightColWidth, boxHeight);
        
        // Titre section
        pdf.setFillColor(248, 249, 250);
        pdf.rect(rightColX, rightY, rightColWidth, 8, 'F');
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(0, 123, 255);
        pdf.text('CODE QR', rightColX + rightColWidth / 2, rightY + 5.5, { align: 'center' });
        rightY += 11; // Réduit de 10 à 11 (ajout de 1mm seulement)
        
        // QR Code centré
        if (qrCodeData) {
            const qrSize = 35; // 35mm
            const qrX = rightColX + (rightColWidth - qrSize) / 2;
            pdf.addImage(qrCodeData, 'PNG', qrX, rightY, qrSize, qrSize);
            rightY += qrSize + 4; // Augmenté de 2 à 4 pour plus d'espace
            
            // Code d'inscription sous le QR
            pdf.setFontSize(9);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(0, 123, 255);
            pdf.text(enrollmentData.enrollment_code, rightColX + rightColWidth / 2, rightY, { align: 'center' });
        }
        
        // TEXTE D'AUTORISATION - En dessous des 2 colonnes (pleine largeur)
        yPos += boxHeight + 8;
        
        const authBoxHeight = 18;
        pdf.setDrawColor(15, 81, 50);
        pdf.setFillColor(209, 231, 221);
        pdf.setLineWidth(0.5);
        pdf.roundedRect(margin, yPos, pageWidth - 2 * margin, authBoxHeight, 2, 2, 'FD');
        
        pdf.setFontSize(9);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(15, 81, 50);
        
        const authText1 = 'L\'ÉLÈVE EST AUTORISÉ(E) À COMMENCER LES COURS';
        const authText2 = `POUR L'ANNÉE SCOLAIRE ${enrollmentData.academic_year}`;
        
        // Centrer verticalement dans le cadre
        const textStartY = yPos + (authBoxHeight / 2) - 2;
        pdf.text(authText1, pageWidth / 2, textStartY, { align: 'center' });
        pdf.text(authText2, pageWidth / 2, textStartY + 5, { align: 'center' });
        yPos += authBoxHeight + 5;
        
        // Signature en bas à droite
        pdf.setFontSize(7);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(100, 100, 100);
        pdf.text('Cachet et Signature', pageWidth - margin - 30, yPos);
        yPos += 8;
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.line(pageWidth - margin - 40, yPos, pageWidth - margin - 10, yPos);
        yPos += 3;
        pdf.text('Direction', pageWidth - margin - 25, yPos, { align: 'center' });
        
        // Pied de page
        pdf.setFontSize(6);
        pdf.setTextColor(100, 100, 100);
        pdf.setDrawColor(200, 200, 200);
        pdf.line(margin, pageHeight - 8, pageWidth - margin, pageHeight - 8);
        
        const footerY = pageHeight - 5;
        pdf.text('Ce document est obligatoire pour accéder à l\'établissement - À conserver précieusement', pageWidth / 2, footerY, { align: 'center' });
        pdf.text(`${schoolSettings.name} - Généré le ${new Date().toLocaleDateString('fr-FR')}`, pageWidth / 2, footerY + 3, { align: 'center' });
        
        // Télécharger le PDF
        const filename = `autorisation_entree_${enrollmentData.enrollment_code}.pdf`;
        pdf.save(filename);
        
        // Rediriger après téléchargement
        setTimeout(() => {
            window.location.href = '{{ route("enrollments.index") }}';
        }, 1000);
        
    } catch (error) {
        console.error('Erreur lors de la génération du PDF:', error);
        alert('Erreur lors de la génération de l\'autorisation d\'entrée. Veuillez réessayer.');
        window.location.href = '{{ route("enrollments.index") }}';
    }
}

// Générer le PDF au chargement de la page
window.addEventListener('load', function() {
    setTimeout(generateAuthorizationPDF, 500);
});
</script>
@endsection

