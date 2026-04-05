<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../components/head.php';
    echo head("User Management", '../');
    ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php
        require_once __DIR__ . '/../components/sidebar.php';
        echo sidebar("user-management");
        ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                require_once __DIR__ . '/../components/backoffice-topbar.php';
                echo topbar();
                ?>
                <div class="container-fluid">
                    <h1>User Management</h1>
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