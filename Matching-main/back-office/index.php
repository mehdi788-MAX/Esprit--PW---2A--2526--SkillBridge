<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../components/head.php';
    echo head("Dashboard", '../');
    ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        require_once __DIR__ . '/../components/sidebar.php';
        echo sidebar('dashboard');
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                require_once __DIR__ . '/../components/backoffice-topbar.php';
                echo topbar();
                ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Tableau de bord</h1>
                    <p class="mb-4">Accédez aux listes gérées par le back-office.</p>
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Liste des demandes</h4>
                                    <p class="card-text text-muted">Voir toutes les demandes clients et les propositions associées à chacune.</p>
                                    <a href="user-management.php" class="btn btn-primary">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Liste des propositions</h4>
                                    <p class="card-text text-muted">Tableau de toutes les propositions reçues, avec la demande liée.</p>
                                    <a href="propositions-list.php" class="btn btn-primary">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php
    require_once __DIR__ . '/../components/imports.php';
    echo imports('../'); ?>
</body>

</html>