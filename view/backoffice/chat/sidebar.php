<?php
// $activePage should be set before including this file
// Values: 'dashboard', 'conversations', 'add_conversation', 'messages', 'chat'
if (!isset($activePage)) $activePage = '';
$tpl = '..';
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $tpl ?>/index.php">
        <div class="sidebar-brand-text mx-3">Skill <sup>Bridge</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
        <a class="nav-link" href="<?= $tpl ?>/dashbord.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Gestion Utilisateurs
    </div>

    <li class="nav-item">
        <a class="nav-link" href="<?= $tpl ?>/users_list.php">
            <i class="fas fa-fw fa-users"></i><span>Liste Utilisateurs</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= $tpl ?>/users_profils.php">
            <i class="fas fa-fw fa-id-card"></i><span>Utilisateurs &amp; Profils</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= $tpl ?>/search_utilisateurs.php">
            <i class="fas fa-fw fa-search"></i><span>Recherche par Rôle</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Gestion Chat
    </div>

    <!-- Nav Item - Conversations -->
    <li class="nav-item <?= ($activePage === 'conversations' || $activePage === 'chat') ? 'active' : '' ?>">
        <a class="nav-link" href="conversations.php">
            <i class="fas fa-fw fa-comments"></i>
            <span>Conversations</span></a>
    </li>

    <!-- Nav Item - Nouvelle Conversation -->
    <li class="nav-item <?= ($activePage === 'add_conversation') ? 'active' : '' ?>">
        <a class="nav-link" href="add_conversation.php">
            <i class="fas fa-fw fa-plus-circle"></i>
            <span>Nouvelle Conversation</span></a>
    </li>

    <!-- Nav Item - Messages -->
    <li class="nav-item <?= ($activePage === 'messages') ? 'active' : '' ?>">
        <a class="nav-link" href="messages.php">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Tous les Messages</span></a>
    </li>
      <li class="nav-item <?= ($activePage === 'messages') ? 'active' : '' ?>">
        <a class="nav-link" href="searchMessages.php">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Chercher les messages </span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Interface
    </div>

    <!-- Nav Item - Components Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
            aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-fw fa-cog"></i>
            <span>Components</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Custom Components:</h6>
                <a class="collapse-item" href="<?= $tpl ?>/buttons.html">Buttons</a>
                <a class="collapse-item" href="<?= $tpl ?>/cards.html">Cards</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Utilities Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
            aria-expanded="true" aria-controls="collapseUtilities">
            <i class="fas fa-fw fa-wrench"></i>
            <span>Utilities</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Custom Utilities:</h6>
                <a class="collapse-item" href="<?= $tpl ?>/utilities-color.html">Colors</a>
                <a class="collapse-item" href="<?= $tpl ?>/utilities-border.html">Borders</a>
                <a class="collapse-item" href="<?= $tpl ?>/utilities-animation.html">Animations</a>
                <a class="collapse-item" href="<?= $tpl ?>/utilities-other.html">Other</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Addons
    </div>

    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages"
            aria-expanded="true" aria-controls="collapsePages">
            <i class="fas fa-fw fa-folder"></i>
            <span>Pages</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Login Screens:</h6>
                <a class="collapse-item" href="<?= $tpl ?>/login.html">Login</a>
                <a class="collapse-item" href="<?= $tpl ?>/register.html">Register</a>
                <a class="collapse-item" href="<?= $tpl ?>/forgot-password.html">Forgot Password</a>
                <div class="collapse-divider"></div>
                <h6 class="collapse-header">Other Pages:</h6>
                <a class="collapse-item" href="<?= $tpl ?>/404.html">404 Page</a>
                <a class="collapse-item" href="<?= $tpl ?>/blank.html">Blank Page</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Charts -->
    <li class="nav-item">
        <a class="nav-link" href="<?= $tpl ?>/charts.html">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Charts</span></a>
    </li>

    <!-- Nav Item - Tables -->
    <li class="nav-item">
        <a class="nav-link" href="<?= $tpl ?>/tables.html">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
