<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Résultat du Test - <?= htmlspecialchars($testData['title']) ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@400;500;700&display=swap');
    :root { --accent: #f97316; --dark: #111111; --light: #f5f5f5; --success: #10b981; --danger: #ef4444; }
    body { font-family: 'Roboto', sans-serif; background: var(--light); color: var(--dark); padding: 40px 0; }
    .result-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center; max-width: 600px; margin: 0 auto; }
    .score-circle { width: 160px; height: 160px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-family: 'Questrial', sans-serif; font-size: 3rem; font-weight: 700; }
    .score-high { border: 8px solid var(--success); color: var(--success); }
    .score-med  { border: 8px solid var(--accent); color: var(--accent); }
    .score-low  { border: 8px solid var(--danger); color: var(--danger); }
    .btn-home { background: var(--dark); color: white; padding: 12px 30px; border-radius: 50px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 20px; transition: 0.2s; }
    .btn-home:hover { background: #333; color: white; }
  </style>
</head>
<body>
<div class="container">
    <div class="result-card">
        <h1 style="font-family:'Questrial',sans-serif;margin-bottom:10px;">Test Terminé !</h1>
        <p class="text-muted mb-4"><?= htmlspecialchars($testData['title']) ?></p>

        <?php 
            $percentage = 0;
            if ($total > 0) {
                $percentage = ($score / $total) * 100;
            }
            
            $scoreClass = 'score-low';
            if ($percentage >= 70) $scoreClass = 'score-high';
            else if ($percentage >= 50) $scoreClass = 'score-med';
        ?>

        <div class="score-circle <?= $scoreClass ?>">
            <?= round($percentage) ?>%
        </div>

        <h4 class="mb-3">Score : <?= $score ?> / <?= $total ?></h4>
        
        <?php if ($percentage >= 70): ?>
            <p style="color:var(--success);font-weight:600;">Félicitations, vous avez réussi le test avec brio !</p>
        <?php elseif ($percentage >= 50): ?>
            <p style="color:var(--accent);font-weight:600;">Bien joué, mais vous pouvez encore vous améliorer.</p>
        <?php else: ?>
            <p style="color:var(--danger);font-weight:600;">Vous devez réviser ce sujet et retenter votre chance.</p>
        <?php endif; ?>

        <a href="index.php?action=frontoffice" class="btn-home">Retour à l'accueil</a>
    </div>
</div>
</body>
</html>
