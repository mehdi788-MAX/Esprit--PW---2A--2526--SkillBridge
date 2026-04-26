<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();

if (is_admin()) {
  header('Location: ' . back_url('index.php'));
  exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SkillBridge - Connectez talent et ambition</title>
  <meta name="description"
    content="SkillBridge connecte les clients avec les meilleurs freelancers. Publiez vos demandes, trouvez le talent qu'il vous faut.">
  <meta name="keywords" content="freelance, marketplace, design, développement, SkillBridge">

  <!-- Favicons -->
  <link href="../../assets/images/favicon.png" rel="icon">
  <link href="../../assets/images/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Noto+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Questrial:wght@400&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../../assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div
      class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="<?= front_url('index.php') ?>" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Accueil</a></li>
          <li><a href="#propositions">Propositions</a></li>
          <li><a href="<?= front_url('mes-demandes.php') ?>"><?= front_demands_label() ?></a></li>
          <?php if (!is_freelancer()): ?>
            <li><a href="<?= front_url('Addrequest.php') ?>">Publier une demande</a></li>
          <?php endif; ?>
          <?php if (!isset($_SESSION["user_id"])): ?>
            <li><a href="<?= front_url('login.php') ?>">Login</a></li>
            <li><a href="<?= front_url('register.php') ?>">S'inscrire</a></li>
          <?php endif; ?>
          <?php if (isset($_SESSION["user_id"])): ?>
            <li><a href="<?= front_url('logout.php') ?>">Logout</a></li>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <div class="header-social-links">
        <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
      </div>

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center content">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <h2>Connecter les talents aux opportunités</h2>
            <p class="lead">Publiez votre besoin, recevez des propositions de freelancers qualifiés et collaborez
              simplement pour concrétiser vos projets.</p>
            <div class="cta-buttons" data-aos="fade-up" data-aos-delay="300">
              <?php if (!is_freelancer()): ?>
                <a href="<?= front_url('Addrequest.php') ?>" class="btn btn-primary">Publier une demande</a>
              <?php endif; ?>
              <a href="#propositions" class="btn btn-outline">Voir les freelancers</a>
            </div>
            <div class="hero-stats" data-aos="fade-up" data-aos-delay="400">
              <div class="stat-item">
                <span class="stat-number">500+</span>
                <span class="stat-label">Freelancers actifs</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">1 200+</span>
                <span class="stat-label">Projets réalisés</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">98%</span>
                <span class="stat-label">Clients satisfaits</span>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="../../assets/images/profile/profile-1.webp" alt="SkillBridge Hero" class="img-fluid"
                data-aos="zoom-out" data-aos-delay="300">
              <div class="shape-1"></div>
              <div class="shape-2"></div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Hero Section -->



    <!-- Skills Section -->
    <section id="skills" class="skills section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-4 skills-animation">

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="skill-box">
              <h3>Design graphique</h3>
              <p>Logo, identité visuelle, illustration et tous vos besoins créatifs.</p>
              <span class="text-end d-block">90%</span>
              <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="skill-box">
              <h3>Développement web</h3>
              <p>Sites vitrines, applications, e-commerce et solutions sur mesure.</p>
              <span class="text-end d-block">95%</span>
              <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="skill-box">
              <h3>Marketing digital</h3>
              <p>Réseaux sociaux, campagnes publicitaires, SEO et stratégie de contenu.</p>
              <span class="text-end d-block">80%</span>
              <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
            <div class="skill-box">
              <h3>Rédaction &amp; SEO</h3>
              <p>Articles de blog, copywriting, traduction et contenu optimisé.</p>
              <span class="text-end d-block">75%</span>
              <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Skills Section -->

    <!-- Resume Section -->
    <section id="resume" class="resume section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Comment ça marche</h2>
        <div class="title-shape">
          <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor"
              stroke-width="2"></path>
          </svg>
        </div>
        <p>En quelques étapes simples, connectez vos besoins au bon freelancer et concrétisez votre projet.</p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row">
          <div class="col-12">
            <div class="resume-wrapper">
              <div class="resume-block" data-aos="fade-up">
                <h2>Pour les clients</h2>
                <p class="lead">Trouvez le freelancer idéal pour votre projet en quelques clics.</p>

                <div class="timeline">
                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="timeline-left">
                      <h4 class="company">Étape 1</h4>
                      <span class="period">Publiez votre demande</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Décrivez votre projet</h3>
                      <p class="description">Remplissez le formulaire de demande avec le titre de votre projet, votre
                        budget, la date limite souhaitée et une description détaillée. Plus votre description est
                        précise, meilleures seront les propositions reçues.</p>
                    </div>
                  </div>

                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="timeline-left">
                      <h4 class="company">Étape 2</h4>
                      <span class="period">Recevez des propositions</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Comparez les offres des freelancers</h3>
                      <p class="description">Des freelancers qualifiés consultent votre demande et vous soumettent leurs
                        propositions.</p>
                      <ul>
                        <li>Consultez les profils et portfolios des freelancers intéressés</li>
                        <li>Comparez les tarifs, délais et expériences proposés</li>
                        <li>Échangez directement avec les candidats pour affiner le projet</li>
                        <li>Choisissez la proposition qui correspond le mieux à vos besoins</li>
                      </ul>
                    </div>
                  </div>

                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="timeline-left">
                      <h4 class="company">Étape 3</h4>
                      <span class="period">Collaborez &amp; livrez</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Recevez votre livrable</h3>
                      <p class="description">Collaborez directement avec votre freelancer et recevez votre livrable dans
                        les délais convenus.</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="resume-block" data-aos="fade-up" data-aos-delay="100">
                <h2>Pour les freelancers</h2>
                <p class="lead">Trouvez des missions qui correspondent à vos compétences et développez votre activité.
                </p>

                <div class="timeline">
                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="timeline-left">
                      <h4 class="company">Étape 1</h4>
                      <span class="period">Créez votre profil</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Mettez en valeur vos compétences</h3>
                      <p class="description">Créez un profil complet avec votre portfolio, vos compétences et vos tarifs
                        pour attirer les bons clients.</p>
                    </div>
                  </div>

                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="timeline-left">
                      <h4 class="company">Étape 2</h4>
                      <span class="period">Consultez les demandes</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Trouvez les missions qui vous correspondent</h3>
                      <p class="description">Parcourez les demandes publiées par les clients et soumettez vos
                        propositions pour les projets qui vous intéressent.</p>
                    </div>
                  </div>

                  <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="timeline-left">
                      <h4 class="company">Étape 3</h4>
                      <span class="period">Développez votre activité</span>
                    </div>
                    <div class="timeline-dot"></div>
                    <div class="timeline-right">
                      <h3 class="position">Construisez votre réputation sur SkillBridge</h3>
                      <p class="description">Livrez des projets de qualité, recevez des avis positifs et développez
                        votre clientèle sur SkillBridge.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Resume Section -->

    <!-- Portfolio Section -->
    <section id="propositions" class="portfolio section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Propositions</h2>
        <div class="title-shape">
          <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor"
              stroke-width="2"></path>
          </svg>
        </div>
        <p>Découvrez les services proposés par nos freelancers dans différentes catégories.</p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">

          <div class="portfolio-filters-container" data-aos="fade-up" data-aos-delay="200">
            <ul class="portfolio-filters isotope-filters">
              <li data-filter="*" class="filter-active">Tout voir</li>
              <li data-filter=".filter-web">Développement web</li>
              <li data-filter=".filter-graphics">Design graphique</li>
              <li data-filter=".filter-motion">Vidéo &amp; Motion</li>
              <li data-filter=".filter-brand">Marketing</li>
              <?php if (is_freelancer()): ?>
                <li class="portfolio-add-link"><a href="<?= front_url('addprop-form.php') ?>">Ajouter proposition</a></li>
              <?php endif; ?>
            </ul>
          </div>

          <div class="row g-4 isotope-container" data-aos="fade-up" data-aos-delay="300">

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-web">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/images/portfolio/portfolio-1.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-1.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-web"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Développement web</span>
                  <h3>Tableau de bord administratif</h3>
                  <p>Interface d'administration moderne et responsive pour la gestion de projets en ligne.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-graphics">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/images/portfolio/portfolio-10.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-10.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-graphics"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Design graphique</span>
                  <h3>Identité visuelle de marque</h3>
                  <p>Création complète d'une identité visuelle : logo, charte graphique et supports de communication.
                  </p>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-motion">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/img/portfolio/portfolio-7.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-7.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-motion"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Vidéo &amp; Motion</span>
                  <h3>Animation de présentation produit</h3>
                  <p>Vidéo animée pour la présentation d'un produit tech avec motion design.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-brand">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/images/portfolio/portfolio-4.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-4.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-brand"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Marketing</span>
                  <h3>Campagne publicitaire réseaux sociaux</h3>
                  <p>Stratégie et création de contenu pour une campagne marketing sur Instagram et Facebook.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-web">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/images/portfolio/portfolio-2.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-2.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-web"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Développement web</span>
                  <h3>Boutique e-commerce</h3>
                  <p>Développement d'une boutique en ligne complète avec gestion des commandes et paiements.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 portfolio-item isotope-item filter-graphics">
              <div class="portfolio-card">
                <div class="portfolio-image">
                  <img src="../../assets/images/portfolio/portfolio-11.jpeg" class="img-fluid" alt="" loading="lazy">
                  <div class="portfolio-overlay">
                    <div class="portfolio-actions">
                      <a href="../../assets/images/portfolio/portfolio-11.jpeg" class="glightbox preview-link"
                        data-gallery="portfolio-gallery-graphics"><i class="bi bi-eye"></i></a>
                    </div>
                  </div>
                </div>
                <div class="portfolio-content">
                  <span class="category">Design graphique</span>
                  <h3>Illustration &amp; création digitale</h3>
                  <p>Collection d'illustrations digitales pour une marque lifestyle.</p>
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>

    </section><!-- /Portfolio Section -->

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section light-background">

      <div class="container section-title" data-aos="fade-up">
        <h2>Témoignages</h2>
        <div class="title-shape">
          <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor"
              stroke-width="2"></path>
          </svg>
        </div>
        <p>Ce que nos clients et freelancers disent de leur expérience sur SkillBridge.</p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="testimonials-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "slidesPerView": 1,
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              }
            }
          </script>

          <div class="swiper-wrapper">

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Un logo livré en 48h, exactement ce que je voulais</h2>
                    <p>
                      J'avais besoin d'un logo pour le lancement de mon e-shop. En publiant ma demande sur SkillBridge,
                      j'ai reçu plusieurs propositions en moins de 24h. Le freelancer que j'ai choisi a parfaitement
                      compris mon brief dès le départ.
                    </p>
                    <p>
                      Je recommande fortement SkillBridge à tous les entrepreneurs qui veulent des résultats rapides et
                      de qualité sans se ruiner.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="../../assets/images/person/person-m-7.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Amira Ben Salem</h3>
                        <span>Cliente – E-commerce</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="../../assets/images/person/person-m-7.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Enfin une plateforme pensée pour les freelancers</h2>
                    <p>
                      Depuis que je suis inscrit sur SkillBridge, je reçois des demandes ciblées qui correspondent
                      vraiment à mes compétences en développement web. Les clients sont sérieux et les délais clairement
                      définis dès le départ.
                    </p>
                    <p>
                      Mon chiffre d'affaires a significativement augmenté grâce aux missions trouvées sur SkillBridge.
                      C'est devenu ma source principale de clients.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="../../assets/images/person/person-f-8.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Karim Laabidi</h3>
                        <span>Freelancer – Développeur web</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="../../assets/images/person/person-f-8.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>La transparence des prix m'a convaincu immédiatement</h2>
                    <p>
                      J'apprécie pouvoir fixer mon budget à l'avance et recevoir uniquement des propositions qui
                      respectent mon enveloppe. Plus de négociations interminables, tout est clair dès le début sur
                      SkillBridge.
                    </p>
                    <p>
                      La fonctionnalité de publication de demandes est vraiment pratique. Je décris mon besoin une fois
                      et les talents viennent à moi. C'est un gain de temps énorme.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="../../assets/images/person/person-m-9.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Sami Riahi</h3>
                        <span>Client – Agence Marketing</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="../../assets/images/person/person-m-9.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Le meilleur moyen de trouver des clients sérieux</h2>
                    <p>
                      En tant que designer graphique freelance, j'avais du mal à trouver des clients réguliers.
                      SkillBridge m'a permis d'accéder à des demandes concrètes avec des budgets et délais définis, ce
                      qui facilite énormément la négociation.
                    </p>
                    <p>
                      La plateforme est simple à utiliser et les clients sont vraiment impliqués dans leurs projets. Je
                      recommande à tous les freelancers de s'y inscrire.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="../../assets/images/person/person-f-10.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Nour Mansouri</h3>
                        <span>Freelancer – Designer graphique</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="../../assets/images/person/person-f-10.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="swiper-navigation w-100 d-flex align-items-center justify-content-center">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>

        </div>

      </div>

    </section><!-- /Testimonials Section -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Nos catégories</h2>
        <div class="title-shape">
          <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor"
              stroke-width="2"></path>
          </svg>
        </div>
        <p>Trouvez le freelancer idéal parmi nos nombreuses catégories de services disponibles sur SkillBridge.</p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center">
          <div class="col-lg-4 mb-5 mb-lg-0">
            <h2 class="fw-bold mb-4 servies-title">Des talents disponibles pour chaque type de projet</h2>
            <p class="mb-4">Que vous ayez besoin d'un logo, d'un site web, d'une vidéo ou d'une stratégie marketing, nos
              freelancers couvrent tous vos besoins créatifs et techniques.</p>
            <?php if (!is_freelancer()): ?>
              <a href="<?= front_url('Addrequest.php') ?>" class="btn btn-outline-primary">Publier une demande</a>
            <?php endif; ?>
          </div>
          <div class="col-lg-8">
            <div class="row g-4">

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-item">
                  <i class="bi bi-palette icon"></i>
                  <h3><a href="<?= front_url('mes-demandes.php') ?>">Design graphique</a></h3>
                  <p>Logo, identité visuelle, illustration, infographie et tous vos besoins en création graphique.</p>
                </div>
              </div>

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="service-item">
                  <i class="bi bi-code-slash icon"></i>
                  <h3><a href="<?= front_url('mes-demandes.php') ?>">Développement web &amp; mobile</a></h3>
                  <p>Sites vitrines, applications web, e-commerce, API et solutions techniques sur mesure.</p>
                </div>
              </div>

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="service-item">
                  <i class="bi bi-megaphone icon"></i>
                  <h3><a href="<?= front_url('mes-demandes.php') ?>">Marketing digital</a></h3>
                  <p>Gestion des réseaux sociaux, campagnes publicitaires, email marketing et stratégie de contenu.</p>
                </div>
              </div>

              <div class="col-md-6" data-aos="fade-up" data-aos-delay="500">
                <div class="service-item">
                  <i class="bi bi-camera-video icon"></i>
                  <h3><a href="<?= front_url('mes-demandes.php') ?>">Vidéo &amp; Motion design</a></h3>
                  <p>Montage vidéo, animation, motion design, réels Instagram et contenu vidéo professionnel.</p>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- Faq Section -->
    <section id="faq" class="faq section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Questions fréquentes</h2>
        <div class="title-shape">
          <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor"
              stroke-width="2"></path>
          </svg>
        </div>
        <p>Tout ce que vous devez savoir sur SkillBridge et son fonctionnement.</p>
      </div>

      <div class="container">

        <div class="row justify-content-center">

          <div class="col-lg-10" data-aos="fade-up" data-aos-delay="100">

            <div class="faq-container">

              <div class="faq-item faq-active">
                <h3>Comment publier une demande sur SkillBridge ?</h3>
                <div class="faq-content">
                  <p>Cliquez sur « Publier une demande » dans le menu, remplissez le formulaire avec le titre de votre
                    projet, votre budget, la date limite souhaitée et une description détaillée. Votre demande sera
                    immédiatement visible par les freelancers disponibles.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <h3>Est-ce que la publication d'une demande est gratuite ?</h3>
                <div class="faq-content">
                  <p>Oui, la publication de demandes est entièrement gratuite pour les clients sur SkillBridge. Vous ne
                    payez qu'au moment de valider la collaboration avec un freelancer.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <h3>Puis-je modifier ou supprimer ma demande après publication ?</h3>
                <div class="faq-content">
                  <p>Oui, vous pouvez modifier ou supprimer vos demandes à tout moment depuis votre espace «
                    Demandes » accessible dans le menu de navigation.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <h3>Comment sont sélectionnés les freelancers sur SkillBridge ?</h3>
                <div class="faq-content">
                  <p>Chaque freelancer crée un profil avec son portfolio et ses compétences. Les clients peuvent
                    consulter les profils, les portfolios et les avis avant de choisir. Notre système de notation
                    garantit la qualité des prestations.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <h3>Quels types de projets puis-je publier sur SkillBridge ?</h3>
                <div class="faq-content">
                  <p>Tous types de projets créatifs et techniques : design graphique, développement web, rédaction,
                    vidéo, marketing, traduction, support client… SkillBridge couvre l'ensemble des services digitaux.
                  </p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

              <div class="faq-item">
                <h3>Combien de temps faut-il pour recevoir des propositions ?</h3>
                <div class="faq-content">
                  <p>En général, les premières propositions arrivent dans les 6 heures suivant la publication de votre
                    demande. Plus votre description est détaillée, plus vous attirerez des propositions pertinentes
                    rapidement.</p>
                </div>
                <i class="faq-toggle bi bi-chevron-right"></i>
              </div>

            </div>

          </div>

        </div>

      </div>

    </section><!-- /Faq Section -->

    <!-- Contact Section -->
    <section id="contact" class="contact section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-5">
          <div class="col-lg-6">
            <div class="content" data-aos="fade-up" data-aos-delay="200">
              <div class="section-category mb-3">Contact</div>
              <h2 class="display-5 mb-4">Une question ? L'équipe SkillBridge est là pour vous aider</h2>
              <p class="lead mb-4">Que vous soyez client ou freelancer, notre équipe est disponible pour répondre à
                toutes vos questions et vous accompagner sur la plateforme.</p>

              <div class="contact-info mt-5">
                <div class="info-item d-flex mb-3">
                  <i class="bi bi-envelope-at me-3"></i>
                  <span>support@skillbridge.tn</span>
                </div>

                <div class="info-item d-flex mb-3">
                  <i class="bi bi-telephone me-3"></i>
                  <span>+216 71 000 000</span>
                </div>

                <div class="info-item d-flex mb-4">
                  <i class="bi bi-geo-alt me-3"></i>
                  <span>Tunis, Tunisie</span>
                </div>

                <a href="#" class="map-link d-inline-flex align-items-center">
                  Voir sur la carte
                  <i class="bi bi-arrow-right ms-2"></i>
                </a>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="contact-form card" data-aos="fade-up" data-aos-delay="300">
              <div class="card-body p-4 p-lg-5">

                <form action="forms/contact.php" method="post" class="php-email-form">
                  <div class="row gy-4">

                    <div class="col-12">
                      <input type="text" name="name" class="form-control" placeholder="Votre nom" required="">
                    </div>

                    <div class="col-12 ">
                      <input type="email" class="form-control" name="email" placeholder="Votre adresse email"
                        required="">
                    </div>

                    <div class="col-12">
                      <input type="text" class="form-control" name="subject" placeholder="Sujet" required="">
                    </div>

                    <div class="col-12">
                      <textarea class="form-control" name="message" rows="6" placeholder="Votre message"
                        required=""></textarea>
                    </div>

                    <div class="col-12 text-center">
                      <div class="loading">Envoi en cours...</div>
                      <div class="error-message"></div>
                      <div class="sent-message">Votre message a été envoyé. Merci !</div>

                      <button type="submit" class="btn btn-submit w-100">Envoyer le message</button>
                    </div>

                  </div>
                </form>

              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer">

    <div class="container">
      <div class="copyright text-center ">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>Tous droits réservés</span>
        </p>
      </div>
      <div class="social-links d-flex justify-content-center">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a> | <a
          href="https://bootstrapmade.com/tools/">DevTools</a>
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/vendor/php-email-form/validate.js"></script>
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../../assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="../../assets/js/main.js"></script>

</body>

</html>
