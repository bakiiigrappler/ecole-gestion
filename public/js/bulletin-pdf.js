// Fonction pour générer le PDF avec html2canvas (reproduit exactement le design du modal)
async function generatePDF(studentData, gradesData, cumulativeScore, cumulativePercentage, term, academicYear, classInfo) {
    try {
        // Vérifier que html2canvas est disponible
        if (typeof html2canvas === 'undefined') {
            throw new Error('html2canvas n\'est pas chargé. Veuillez recharger la page.');
        }

        // Vérifier que jsPDF est disponible
        if (typeof window.jspdf === 'undefined') {
            throw new Error('jsPDF n\'est pas chargé. Veuillez recharger la page.');
        }

        // Créer un conteneur temporaire pour le bulletin
        const tempContainer = document.createElement('div');
        tempContainer.style.position = 'absolute';
        tempContainer.style.left = '-9999px';
        tempContainer.style.top = '-9999px';
        tempContainer.style.width = '210mm'; // A4 portrait width
        tempContainer.style.height = '297mm'; // A4 portrait height
        tempContainer.style.backgroundColor = 'white';
        tempContainer.style.padding = '0';
        tempContainer.style.margin = '0';
        tempContainer.style.fontFamily = 'Arial, sans-serif';
        tempContainer.style.boxSizing = 'border-box';
        document.body.appendChild(tempContainer);

        // Copier le contenu du modal dans le conteneur temporaire
        const modalContent = document.querySelector('.bulletin-page');
        if (!modalContent) {
            throw new Error('Contenu du bulletin non trouvé dans le modal');
        }

        // Cloner le contenu du modal
        const clonedContent = modalContent.cloneNode(true);
        tempContainer.appendChild(clonedContent);

        // Attendre que le contenu soit rendu
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Capturer le contenu avec html2canvas
        const canvas = await html2canvas(tempContainer, {
            scale: 2, // Haute qualité
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            width: tempContainer.scrollWidth,
            height: tempContainer.scrollHeight
        });

        // Supprimer le conteneur temporaire
        document.body.removeChild(tempContainer);

        // Créer le PDF avec jsPDF
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        // Ajouter l'image pour couvrir toute la page
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, pdfWidth, pdfHeight);
        
        // Télécharger le PDF
        const fileName = `bulletin_${studentData.first_name}_${studentData.last_name}_${term || 'trimestre'}.pdf`;
        pdf.save(fileName);

    } catch (error) {
        console.error('Erreur lors de la génération du PDF avec html2canvas:', error);
        alert('Erreur lors de la génération du PDF: ' + error.message);
    }
}
