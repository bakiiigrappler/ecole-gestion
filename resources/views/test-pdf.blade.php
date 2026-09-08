<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            font-size: 16px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Généré le {{ $date }}</p>
    </div>
    
    <div class="content">
        <p>{{ $content }}</p>
        <p>Ce test confirme que l'export PDF fonctionne correctement.</p>
    </div>
</body>
</html>
