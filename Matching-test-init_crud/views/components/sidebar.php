<?php

function sidebar($active)
{
    return "
<ul class='navbar-nav bg-gradient-primary sidebar sidebar-dark accordion' id='accordionSidebar'>

    <a class='sidebar-brand d-flex align-items-center justify-content-center' href='index.php'>
        <div class='sidebar-brand-text mx-3'>Skill <sup>Bridge</sup></div>
    </a>

    <hr class='sidebar-divider'>

    <li class='nav-item " . ($active === 'dashboard' ? "active" : "") . "'>
        <a class='nav-link' href='index.php'>
            <i class='fas fa-fw fa-tachometer-alt'></i>
            <span>Tableau de bord</span>
        </a>
    </li>

    <li class='nav-item " . ($active === 'user-management' ? "active" : "") . "'>
        <a class='nav-link' href='user-management.php'>
            <i class='fas fa-fw fa-list'></i>
            <span>Liste des demandes</span>
        </a>
    </li>

    <li class='nav-item " . ($active === 'propositions-list' ? "active" : "") . "'>
        <a class='nav-link' href='propositions-list.php'>
            <i class='fas fa-fw fa-comments'></i>
            <span>Liste des propositions</span>
        </a>
    </li>

    <li class='nav-item'>
        <a class='nav-link' href='/pleaseeee/Matching-test-init_crud/views/front-office/index.html' target='_blank' rel='noopener'>
            <i class='fas fa-fw fa-external-link-alt'></i>
            <span>Site public</span>
        </a>
    </li>

    <li class='nav-item'>
        <a class='nav-link' href='#'>
            <span>Commande</span>
        </a>
    </li>

    <li class='nav-item'>
        <a class='nav-link' href='#'>
            <span>Validation</span>
        </a>
    </li>

    <li class='nav-item'>
        <a class='nav-link' href='#'>
            <span>Chat</span>
        </a>
    </li>

    <li class='nav-item'>
        <a class='nav-link' href='#'>
            <span>Déconnecter</span>
        </a>
    </li>

</ul>
";
}
