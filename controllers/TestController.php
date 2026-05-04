<?php
// controllers/TestController.php

require_once "config/Database.php";
require_once "models/Test.php";
require_once "models/Category.php";
require_once "models/Question.php";
require_once "models/Resultat.php";

class TestController {

    private $db;
    private $test;
    private $category;

    public function __construct() {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->test     = new Test($this->db);
        $this->category = new Category($this->db);
    }

    // --- GETTERS ---
    public function getDb() {
        return $this->db;
    }

    public function getTest() {
        return $this->test;
    }

    public function getCategory() {
        return $this->category;
    }

    // --- SETTERS ---
    public function setDb($db) {
        $this->db = $db;
    }

    public function setTest($test) {
        $this->test = $test;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    // Backoffice — page unifiée (tests + catégories)
    public function index() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
        require_once "views/backoffice/index.php";
    }

    // Formulaire d'ajout de test
    public function create() {
        $categories = $this->category->getAll();
        require_once "views/backoffice/create.php";
    }

    // Enregistrer un nouveau test (POST)
    public function store() {
        $this->test->title         = $_POST['title'];
        $this->test->category_id   = $_POST['category_id'];
        $this->test->duration      = $_POST['duration'];
        $this->test->level         = $_POST['level'];
        $this->test->average_score = $_POST['average_score'];

        if ($this->test->create()) {
            header("Location: index.php?action=index&success=ajout");
        } else {
            header("Location: index.php?action=create&error=1");
        }
        exit();
    }

    // Formulaire de modification de test
    public function edit() {
        $this->test->id = $_GET['id'];
        $stmt           = $this->test->getById();
        $test_data      = $stmt->fetch(PDO::FETCH_ASSOC);
        $categories     = $this->category->getAll();
        require_once "views/backoffice/edit.php";
    }

    // Enregistrer les modifications d'un test (POST)
    public function update() {
        $this->test->id            = $_POST['id'];
        $this->test->title         = $_POST['title'];
        $this->test->category_id   = $_POST['category_id'];
        $this->test->duration      = $_POST['duration'];
        $this->test->level         = $_POST['level'];
        $this->test->average_score = $_POST['average_score'];

        if ($this->test->update()) {
            header("Location: index.php?action=index&success=modif");
        } else {
            header("Location: index.php?action=edit&id=" . $_POST['id'] . "&error=1");
        }
        exit();
    }

    // Supprimer un test
    public function delete() {
        $this->test->id = $_GET['id'];

        if ($this->test->delete()) {
            header("Location: index.php?action=index&success=suppression");
        } else {
            header("Location: index.php?action=index&error=1");
        }
        exit();
    }

    // Frontoffice — vue client avec filtres
    public function frontoffice() {
        $all_tests_raw = $this->test->getAll()->fetchAll(PDO::FETCH_ASSOC);
        $categories    = $this->category->getAll()->fetchAll(PDO::FETCH_ASSOC);

        $filter_cat   = isset($_GET['cat'])   ? $_GET['cat']   : '';
        $filter_level = isset($_GET['level']) ? $_GET['level'] : '';

        $all_tests = array_filter($all_tests_raw, function($test) use ($filter_cat, $filter_level) {
            $match_cat   = ($filter_cat   === '' || $test['category_id'] == $filter_cat);
            $match_level = ($filter_level === '' || $test['level'] == $filter_level);
            return $match_cat && $match_level;
        });

        require_once "views/frontoffice/index.php";
    }

    // Générer un test avec l'IA Gemini
    public function generateAI() {
        if (!defined('GEMINI_API_KEY')) {
            die("API Key non définie.");
        }

        $test_id = $_GET['id'];
        $this->test->id = $test_id;
        $testData = $this->test->getById()->fetch(PDO::FETCH_ASSOC);

        if (!$testData) {
            header("Location: index.php?action=index&error=test_not_found");
            exit();
        }

        $numQuestions = 5;
        if ($testData['level'] == 'Moyen') $numQuestions = 10;
        if ($testData['level'] == 'Avancé') $numQuestions = 15;

        $prompt = "Génère exactement $numQuestions questions à choix multiples (QCM) en français sur le sujet : " . $testData['title'] . " (Catégorie: " . $testData['category_name'] . ", Niveau: " . $testData['level'] . "). " .
        "Renvoie UNIQUEMENT un tableau JSON (pas d'objet racine) avec ce format exact, sans aucun autre texte (pas de markdown ```json) : " .
        "[{\"question\": \"texte de la question\", \"option_a\": \"réponse A\", \"option_b\": \"réponse B\", \"option_c\": \"réponse C\", \"option_d\": \"réponse D\", \"bonne_reponse\": \"a\"}] " .
        "La bonne réponse doit être uniquement la lettre en minuscule: a, b, c ou d. option_c et option_d sont obligatoires pour un qcm.";

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . GEMINI_API_KEY;

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            $error = error_get_last();
            file_put_contents('ai_debug.txt', "API ERROR: " . print_r($error, true));
            header("Location: index.php?action=index&error=api_error");
            exit();
        }

        file_put_contents('ai_debug.txt', "RAW RESPONSE: " . $result);

        $response = json_decode($result, true);
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $jsonText = $response['candidates'][0]['content']['parts'][0]['text'];
            
            $questions = null;
            // Essayer de trouver le JSON avec une expression régulière (tout ce qui est entre crochets [])
            if (preg_match('/\[.*\]/s', $jsonText, $matches)) {
                $questions = json_decode($matches[0], true);
            }
            
            // Si l'expression régulière échoue, essayer la méthode classique
            if (!$questions) {
                $cleanText = str_replace(['```json', '```', 'JSON'], '', $jsonText);
                $questions = json_decode(trim($cleanText), true);
            }

            if (is_array($questions)) {
                $questionModel = new Question($this->db);
                $questionModel->deleteAllByTestId($test_id); // Supprimer l'ancien
                
                foreach ($questions as $q) {
                    $questionModel->test_id = $test_id;
                    $questionModel->question = $q['question'];
                    $questionModel->type = 'qcm';
                    $questionModel->option_a = $q['option_a'];
                    $questionModel->option_b = $q['option_b'];
                    $questionModel->option_c = isset($q['option_c']) ? $q['option_c'] : null;
                    $questionModel->option_d = isset($q['option_d']) ? $q['option_d'] : null;
                    $questionModel->bonne_reponse = strtolower($q['bonne_reponse']);
                    $questionModel->create();
                }
                header("Location: index.php?action=index&success=ai_generated");
                exit();
            }
        }
        header("Location: index.php?action=index&error=json_parse");
        exit();
    }

    // Afficher le formulaire pour passer un test (côté client)
    public function takeTest() {
        $test_id = $_GET['id'];
        $this->test->id = $test_id;
        $testData = $this->test->getById()->fetch(PDO::FETCH_ASSOC);

        $questionModel = new Question($this->db);
        $questions = $questionModel->getByTestId($test_id)->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            header("Location: index.php?action=frontoffice&error=not_generated");
            exit();
        }

        require_once "views/frontoffice/take_test.php";
    }

    // Traiter la soumission du test (côté client)
    public function submitTest() {
        $test_id = $_POST['test_id'];

        $questionModel = new Question($this->db);
        $questions = $questionModel->getByTestId($test_id)->fetchAll(PDO::FETCH_ASSOC);

        $score = 0;
        $total = count($questions);
        $detailsArr = [];

        foreach ($questions as $q) {
            $q_id = $q['id'];
            $user_ans = isset($_POST['q_'.$q_id]) ? $_POST['q_'.$q_id] : null;
            $correct = false;

            if ($user_ans && strtolower($user_ans) === strtolower($q['bonne_reponse'])) {
                $score++;
                $correct = true;
            }

            $detailsArr[] = [
                'question_id' => $q_id,
                'user_answer' => $user_ans,
                'correct'     => $correct
            ];
        }

        $resultatModel = new Resultat($this->db);
        $resultatModel->test_id = $test_id;
        $resultatModel->score   = $score;
        $resultatModel->total   = $total;
        $resultatModel->details = json_encode($detailsArr);
        $resultatModel->create();

        $this->test->id = $test_id;
        $testData = $this->test->getById()->fetch(PDO::FETCH_ASSOC);

        require_once "views/frontoffice/test_result.php";
    }
    // Exporter un test en PDF (Backoffice)
    public function exportPdf() {
        $test_id = $_GET['id'];
        $this->test->id = $test_id;
        $testData = $this->test->getById()->fetch(PDO::FETCH_ASSOC);

        if (!$testData) {
            header("Location: index.php?action=index&error=test_not_found");
            exit();
        }

        $questionModel = new Question($this->db);
        $questions = $questionModel->getByTestId($test_id)->fetchAll(PDO::FETCH_ASSOC);

        // Include FPDF
        require_once "lib/fpdf/fpdf.php";

        // Extend FPDF to add a footer if we want, or just use basic
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Use standard fonts
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, utf8_decode('Rapport de Test: ' . $testData['title']), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(0, 8, utf8_decode('Catégorie: ' . $testData['category_name']), 0, 1);
        $pdf->Cell(0, 8, utf8_decode('Niveau: ' . $testData['level']), 0, 1);
        $pdf->Cell(0, 8, utf8_decode('Score Moyen: ' . $testData['average_score'] . '%'), 0, 1);
        $pdf->Ln(10);

        foreach ($questions as $index => $q) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->MultiCell(0, 8, utf8_decode(($index + 1) . '. ' . $q['question']));
            
            $pdf->SetFont('Arial', '', 11);
            $options = [
                'a' => $q['option_a'],
                'b' => $q['option_b'],
                'c' => $q['option_c'],
                'd' => $q['option_d']
            ];
            
            foreach ($options as $key => $val) {
                if ($val) {
                    // Highlight correct answer
                    if (strtolower($q['bonne_reponse']) === $key) {
                        $pdf->SetFont('Arial', 'B', 11);
                        $pdf->SetTextColor(0, 128, 0); // Green for correct answer
                        $pdf->Cell(0, 7, utf8_decode('    [' . strtoupper($key) . '] ' . $val . ' (Bonne réponse)'), 0, 1);
                        $pdf->SetTextColor(0, 0, 0); // Reset color
                        $pdf->SetFont('Arial', '', 11);
                    } else {
                        $pdf->Cell(0, 7, utf8_decode('    [' . strtoupper($key) . '] ' . $val), 0, 1);
                    }
                }
            }
            $pdf->Ln(5);
        }

        // Clean output buffer to prevent PDF corruption
        if (ob_get_length()) ob_end_clean();
        
        $pdf->Output('I', 'test_' . $test_id . '.pdf');
    }

    // Afficher l'historique des tests passés (Frontoffice)
    public function history() {
        $resultatModel = new Resultat($this->db);
        $recentResults = $resultatModel->getRecent(20)->fetchAll(PDO::FETCH_ASSOC);

        require_once "views/frontoffice/history.php";
    }
}
?>
