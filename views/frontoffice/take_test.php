<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Passer le Test - <?= htmlspecialchars($testData['title']) ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@400;500;700&display=swap');
    :root { --accent: #f97316; --dark: #111111; --light: #f5f5f5; }
    body { font-family: 'Roboto', sans-serif; background: var(--light); color: var(--dark); padding: 40px 0; }
    .test-header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .test-title { font-family: 'Questrial', sans-serif; color: var(--accent); margin: 0; font-size: 1.8rem; }
    .q-card { background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .q-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 15px; color: var(--dark); }
    .option-label { display: block; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; transition: 0.2s; margin-bottom: 10px; font-size: 0.95rem; }
    .option-label:hover { background: #fff5f0; border-color: #ffc9a8; }
    .form-check-input:checked + .option-label { background: #fff5f0; border-color: var(--accent); color: var(--accent); font-weight: 600; }
    .btn-submit { background: var(--accent); color: white; padding: 12px 30px; border-radius: 50px; font-weight: 600; border: none; transition: 0.2s; font-size: 1.05rem; }
    .btn-submit:hover { background: #ea6c0a; }
  </style>
</head>
<body>
<div class="container">
    <div class="test-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="test-title"><?= htmlspecialchars($testData['title']) ?></h1>
            <p class="text-muted mb-0">Catégorie : <?= htmlspecialchars($testData['category_name']) ?> | Niveau : <?= htmlspecialchars($testData['level']) ?></p>
        </div>
        <div style="font-weight:700;color:#e74a3b; font-size: 1.2rem;">
            ⏱ <span id="timer">--:--</span>
        </div>
    </div>

    <form id="testForm" action="index.php?action=submit_test" method="POST">
        <input type="hidden" name="test_id" value="<?= $test_id ?>">

    <script>
        // Durée en minutes convertie en secondes
        let timeLeft = <?= (int)$testData['duration'] * 60 ?>;
        const timerElement = document.getElementById('timer');
        const formElement = document.getElementById('testForm');

        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;

            // Formater avec un zéro devant si < 10
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            timerElement.innerText = minutes + ":" + seconds;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert("Le temps est écoulé ! Vos réponses vont être soumises automatiquement.");
                formElement.submit();
            }
            timeLeft--;
        }

        updateTimer(); // Lancement immédiat
        const timerInterval = setInterval(updateTimer, 1000);
    </script>
        
        <?php $i = 1; foreach ($questions as $q): ?>
        <div class="q-card">
            <div class="q-title">Q<?= $i ?>. <?= htmlspecialchars($q['question']) ?></div>
            
            <?php foreach (['a', 'b', 'c', 'd'] as $letter): ?>
                <?php if (!empty($q['option_'.$letter])): ?>
                <div class="form-check p-0">
                    <input class="form-check-input d-none" type="radio" name="q_<?= $q['id'] ?>" id="q_<?= $q['id'] ?>_<?= $letter ?>" value="<?= $letter ?>" required>
                    <label class="option-label" for="q_<?= $q['id'] ?>_<?= $letter ?>">
                        <strong><?= strtoupper($letter) ?>.</strong> <?= htmlspecialchars($q['option_'.$letter]) ?>
                    </label>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php $i++; endforeach; ?>
        
        <div class="text-center mt-4 mb-5">
            <button type="submit" class="btn-submit">Soumettre mes réponses</button>
        </div>
    </form>
</div>
</body>
</html>
