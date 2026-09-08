<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin - {{ $student->first_name }} {{ $student->last_name }} - {{ $trimester }}</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .bulletin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16px;
            margin: 10px 0 0 0;
            font-weight: bold;
        }
        
        .school-info {
            margin-top: 15px;
            font-size: 12px;
        }
        
        .student-info {
            margin-bottom: 30px;
            border: 1px solid #333;
            padding: 15px;
        }
        
        .student-info h3 {
            font-size: 14px;
            margin: 0 0 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .info-value {
            flex: 1;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .grades-table th,
        .grades-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }
        
        .grades-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .grades-table td:first-child {
            text-align: left;
            font-weight: bold;
        }
        
        .totals-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .summary {
            margin-top: 30px;
            border: 1px solid #333;
            padding: 15px;
        }
        
        .summary h3 {
            font-size: 14px;
            margin: 0 0 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="bulletin-container">
        <!-- En-tête -->
        <div class="header">
            <h1>{{ $schoolName ?? 'Établissement Scolaire' }}</h1>
            <h2>BULLETIN DE NOTES - {{ strtoupper($trimester) }}</h2>
            <div class="school-info">
                <p>Année scolaire : {{ $academicYear->name ?? '2024-2025' }}</p>
            </div>
        </div>

        <!-- Informations de l'élève -->
        <div class="student-info">
            <h3>Informations de l'élève</h3>
            <div class="info-row">
                <div class="info-label">Nom et prénoms :</div>
                <div class="info-value">{{ $student->first_name }} {{ $student->last_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Classe :</div>
                <div class="info-value">{{ $class->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Niveau :</div>
                <div class="info-value">{{ $class->level->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de naissance :</div>
                <div class="info-value">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/C' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Sexe :</div>
                <div class="info-value">{{ ucfirst($student->gender ?? 'N/C') }}</div>
            </div>
        </div>

        <!-- Tableau des notes -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th>Matières</th>
                    <th>Note /20</th>
                    <th>Coeff</th>
                    <th>Note × Coeff</th>
                    <th>Rang</th>
                    <th>Absences</th>
                    <th>Appréciation</th>
                    <th>Professeur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tableDataForPDF as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                    @endforeach
                </tr>
                @endforeach
                <tr class="totals-row">
                    @foreach($totalsRowForPDF as $cell)
                    <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <!-- Résumé -->
        <div class="summary">
            <h3>Résumé du trimestre</h3>
            <div class="summary-row">
                <span>Moyenne trimestrielle :</span>
                <span><strong>{{ $trimesterData['cumulative_score'] }}/20</strong></span>
            </div>
            <div class="summary-row">
                <span>Nombre de matières :</span>
                <span><strong>{{ $trimesterData['total_subjects'] }}</strong></span>
            </div>
            <div class="summary-row">
                <span>Statut :</div>
                <span><strong>{{ $trimesterData['cumulative_score'] >= 10 ? 'Admis' : 'Non admis' }}</strong></span>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Bulletin généré le {{ date('d/m/Y à H:i') }}</p>
            <p>{{ $schoolName ?? 'Établissement Scolaire' }}</p>
        </div>
    </div>
</body>
</html>
