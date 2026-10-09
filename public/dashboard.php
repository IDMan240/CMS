<?php

/* =========================================================
   CALIBRATION MANAGEMENT SYSTEM
   PROTECTED ADMIN DASHBOARD
========================================================= */

session_start();

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

/* =========================================================
   USER INFORMATION
========================================================= */

$userName =
    $_SESSION["full_name"]
    ?? $_SESSION["username"]
    ?? "User";

$userRole =
    $_SESSION["role"]
    ?? "technician";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        CMS | Dashboard
    </title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f4f4;

            color: #111111;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: 245px;

            background: #111111;

            color: #ffffff;

            z-index: 1000;

            display: flex;

            flex-direction: column;

            transition:
                transform .25s ease;
        }


        /* =====================================================
           SIDEBAR BRAND
        ===================================================== */

        .sidebar-brand {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 22px 18px;

            border-bottom:
                1px solid
                rgba(255,255,255,.12);
        }


        .sidebar-logo {

            width: 43px;

            height: 43px;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                3px solid
                #d00000;

            border-radius: 50%;

            color: #ffffff;

            font-size: 10px;

            font-weight: bold;
        }


        .sidebar-brand strong {

            display: block;

            font-size: 17px;

            letter-spacing: 3px;
        }


        .sidebar-brand small {

            display: block;

            margin-top: 4px;

            color: #999999;

            font-size: 7px;

            letter-spacing: 1px;
        }


        /* =====================================================
           NAVIGATION
        ===================================================== */

        .sidebar-nav {

            flex: 1;

            padding: 18px 12px;

            overflow-y: auto;
        }


        .nav-label {

            margin:

                8px 10px

                12px;

            color: #777777;

            font-size: 8px;

            font-weight: bold;

            letter-spacing: 2px;
        }


        .nav-item {

            display: flex;

            align-items: center;

            gap: 12px;

            width: 100%;

            padding: 13px 13px;

            margin-bottom: 5px;

            border-left:
                3px solid
                transparent;

            color: #bbbbbb;

            background: transparent;

            text-decoration: none;

            font-size: 11px;

            cursor: pointer;

            transition:
                .2s ease;
        }


        .nav-item:hover {

            color: #ffffff;

            background:
                rgba(255,255,255,.07);

            border-left-color:
                #d00000;
        }


        .nav-item.active {

            color: #ffffff;

            background:
                #d00000;

            border-left-color:
                #ffffff;
        }


        .nav-icon {

            width: 25px;

            min-width: 25px;

            height: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid
                rgba(255,255,255,.3);

            font-size: 12px;
        }


        .nav-item.active .nav-icon {

            border-color:
                #ffffff;
        }


        /* =====================================================
           SIDEBAR BOTTOM
        ===================================================== */

        .sidebar-bottom {

            padding: 15px;

            border-top:
                1px solid
                rgba(255,255,255,.12);
        }


        .sidebar-user {

            padding: 10px;

            margin-bottom: 10px;

            background:
                rgba(255,255,255,.05);
        }


        .sidebar-user strong {

            display: block;

            color: #ffffff;

            font-size: 10px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .sidebar-user small {

            display: block;

            margin-top: 4px;

            color: #d00000;

            font-size: 8px;

            font-weight: bold;

            text-transform: uppercase;
        }


        .logout-sidebar {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 100%;

            padding: 12px;

            border:
                1px solid
                #d00000;

            background:
                transparent;

            color: #ffffff;

            text-decoration: none;

            font-size: 9px;

            font-weight: bold;

            letter-spacing: 1px;

            transition: .2s;
        }


        .logout-sidebar:hover {

            background: #d00000;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: 245px;

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 75px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 28px;

            background: #ffffff;

            border-bottom:
                3px solid
                #d00000;

            position: sticky;

            top: 0;

            z-index: 500;
        }


        .mobile-menu {

            display: none;

            width: 40px;

            height: 40px;

            border: 1px solid #dddddd;

            background: #ffffff;

            color: #111111;

            font-size: 21px;

            cursor: pointer;
        }


        .top-title span {

            display: block;

            color: #d00000;

            font-size: 8px;

            font-weight: bold;

            letter-spacing: 2px;
        }


        .top-title strong {

            display: block;

            margin-top: 4px;

            font-size: 19px;
        }


        .top-user {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .top-user-info {

            text-align: right;
        }


        .top-user-info strong {

            display: block;

            font-size: 11px;
        }


        .top-user-info small {

            display: block;

            margin-top: 3px;

            color: #d00000;

            font-size: 8px;

            font-weight: bold;

            text-transform: uppercase;
        }


        .top-logout {

            padding: 11px 16px;

            background: #d00000;

            border: 2px solid #d00000;

            color: #ffffff;

            text-decoration: none;

            font-size: 9px;

            font-weight: bold;

            letter-spacing: 1px;
        }


        .top-logout:hover {

            background: #111111;

            border-color: #111111;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            width: 100%;

            max-width: 1250px;

            margin: 0 auto;

            padding: 30px;
        }


        /* =====================================================
           WELCOME
        ===================================================== */

        .welcome {

            padding: 30px;

            margin-bottom: 25px;

            border-left:
                6px solid
                #d00000;

            border-top:
                1px solid
                #dddddd;

            border-right:
                1px solid
                #dddddd;

            border-bottom:
                1px solid
                #dddddd;

            background: #ffffff;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.06);
        }


        .welcome-label {

            color: #d00000;

            font-size: 8px;

            font-weight: bold;

            letter-spacing: 3px;
        }


        .welcome h1 {

            margin: 8px 0;

            font-size: 29px;
        }


        .welcome h1 span {

            color: #d00000;
        }


        .welcome p {

            margin: 0;

            color: #666666;

            font-size: 11px;
        }


        /* =====================================================
           DASHBOARD CARDS
        ===================================================== */

        .section-title {

            margin:
                0 0 15px;

            color: #111111;

            font-size: 17px;
        }


        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;
        }


        .card {

            min-height: 170px;

            padding: 22px;

            border:
                1px solid
                #dddddd;

            background: #ffffff;

            color: #111111;

            text-decoration: none;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,.05);

            transition:
                .2s ease;
        }


        .card:hover {

            transform:
                translateY(-3px);

            border-color:
                #d00000;

            box-shadow:
                0 7px 20px
                rgba(0,0,0,.10);
        }


        .card-icon {

            width: 48px;

            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 22px;

            border:
                2px solid
                #111111;

            color:
                #d00000;

            background:
                #ffffff;

            font-size: 17px;

            font-weight: bold;
        }


        .card strong {

            display: block;

            font-size: 14px;
        }


        .card small {

            display: block;

            margin-top: 8px;

            color: #777777;

            font-size: 9px;

            line-height: 1.5;
        }


        .card-arrow {

            display: block;

            margin-top: 15px;

            color: #d00000;

            font-size: 12px;

            font-weight: bold;
        }


        /* =====================================================
           ADMIN PANEL
        ===================================================== */

        .admin-panel {

            margin-top: 25px;

            padding: 25px;

            border:
                1px solid
                #dddddd;

            border-top:
                5px solid
                #d00000;

            background: #ffffff;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.06);
        }


        .admin-panel h2 {

            margin:
                0 0 8px;

            font-size: 17px;
        }


        .admin-panel p {

            margin: 0;

            color: #666666;

            font-size: 10px;
        }


        .admin-badge {

            display: inline-block;

            margin-top: 15px;

            padding: 8px 12px;

            color: #ffffff;

            background: #d00000;

            font-size: 8px;

            font-weight: bold;

            letter-spacing: 1px;
        }


        /* =====================================================
           MOBILE OVERLAY
        ===================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0,0,0,.55);

            z-index: 900;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1000px) {

            .cards {

                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 750px) {

            .sidebar {

                transform:
                    translateX(-100%);
            }


            .sidebar.open {

                transform:
                    translateX(0);
            }


            .sidebar-overlay.open {

                display: block;
            }


            .main {

                margin-left: 0;
            }


            .mobile-menu {

                display: block;
            }


            .topbar {

                padding:
                    0 15px;

                gap: 12px;
            }


            .top-title {

                flex: 1;

                margin-left: 10px;
            }


            .top-title strong {

                font-size: 16px;
            }


            .top-user-info {

                display: none;
            }


            .top-logout {

                padding:
                    10px 12px;
            }


            .content {

                padding:
                    18px 15px;
            }


            .welcome {

                padding:
                    23px;
            }


            .welcome h1 {

                font-size: 23px;
            }


            .cards {

                grid-template-columns:
                    1fr;
            }

        }


        /* =====================================================
           SMALL PHONES
        ===================================================== */

        @media (max-width: 430px) {

            .top-title span {

                font-size: 7px;
            }


            .top-title strong {

                font-size: 14px;
            }


            .top-logout {

                font-size: 8px;

                padding:
                    9px 10px;
            }


            .welcome h1 {

                font-size: 21px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="sidebar"
    id="sidebar"
>


    <div class="sidebar-brand">

        <div class="sidebar-logo">
            CAL
        </div>

        <div>

            <strong>
                CMS
            </strong>

            <small>
                CALIBRATION MANAGEMENT SYSTEM
            </small>

        </div>

    </div>


    <nav class="sidebar-nav">


        <div class="nav-label">
            MAIN MENU
        </div>


        <a
            href="dashboard.php"
            class="nav-item active"
        >

            <span class="nav-icon">
                ⌂
            </span>

            Dashboard

        </a>


        <a
            href="gauge.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ◉
            </span>

            Gauge

        </a>


        <a
            href="recorder.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ◈
            </span>

            Recorder

        </a>


        <a
            href="valve.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ◇
            </span>

            Valve Test Certificate

        </a>


        <a
            href="certificates.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ▤
            </span>

            Certificates

        </a>


        <a
            href="verify.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ⌕
            </span>

            Verify Certificate

        </a>


        <a
            href="settings.php"
            class="nav-item"
        >

            <span class="nav-icon">
                ⚙
            </span>

            Settings

        </a>

    </nav>


    <div class="sidebar-bottom">


        <div class="sidebar-user">

            <strong>
                <?= htmlspecialchars($userName) ?>
            </strong>

            <small>
                <?= htmlspecialchars($userRole) ?>
            </small>

        </div>


        <a
            href="../auth/logout.php"
            class="logout-sidebar"
        >
            ⇥ &nbsp; LOGOUT
        </a>

    </div>

</aside>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">


        <button
            type="button"
            class="mobile-menu"
            id="mobileMenu"
        >
            ☰
        </button>


        <div class="top-title">

            <span>
                CALIBRATION MANAGEMENT SYSTEM
            </span>

            <strong>
                Dashboard
            </strong>

        </div>


        <div class="top-user">


            <div class="top-user-info">

                <strong>
                    <?= htmlspecialchars($userName) ?>
                </strong>

                <small>
                    <?= htmlspecialchars($userRole) ?>
                </small>

            </div>


            <a
                href="../auth/logout.php"
                class="top-logout"
            >
                LOGOUT
            </a>


        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- WELCOME -->

        <section class="welcome">

            <span class="welcome-label">
                CALIBRATION MANAGEMENT SYSTEM
            </span>


            <h1>

                Welcome back,

                <span>
                    <?= htmlspecialchars($userName) ?>
                </span>

            </h1>


            <p>
                Your PHP authentication session
                is working successfully.
            </p>

        </section>


        <!-- MODULES -->

        <h2 class="section-title">
            Calibration Modules
        </h2>


        <section class="cards">


            <a
                href="gauge.php"
                class="card"
            >

                <div class="card-icon">
                    ◉
                </div>

                <strong>
                    Gauge
                </strong>

                <small>
                    Generate and manage
                    gauge calibration certificates.
                </small>

                <span class="card-arrow">
                    OPEN →
                </span>

            </a>


            <a
                href="recorder.php"
                class="card"
            >

                <div class="card-icon">
                    ◈
                </div>

                <strong>
                    Recorder
                </strong>

                <small>
                    Generate and manage
                    recorder calibration certificates.
                </small>

                <span class="card-arrow">
                    OPEN →
                </span>

            </a>


            <a
                href="valve.php"
                class="card"
            >

                <div class="card-icon">
                    ◇
                </div>

                <strong>
                    Valve Test
                </strong>

                <small>
                    Generate and manage
                    valve test certificates.
                </small>

                <span class="card-arrow">
                    OPEN →
                </span>

            </a>


            <a
                href="certificates.php"
                class="card"
            >

                <div class="card-icon">
                    ▤
                </div>

                <strong>
                    Certificates
                </strong>

                <small>
                    View, search and manage
                    calibration certificates.
                </small>

                <span class="card-arrow">
                    OPEN →
                </span>

            </a>


            <a
                href="verify.php"
                class="card"
            >

                <div class="card-icon">
                    ⌕
                </div>

                <strong>
                    Verify Certificate
                </strong>

                <small>
                    Verify calibration certificates
                    using certificate information.
                </small>

                <span class="card-arrow">
                    OPEN →
                </span>

            </a>


        </section>


        <!-- ADMIN PANEL -->

        <?php if ($userRole === "admin"): ?>

            <section class="admin-panel">

                <h2>
                    Administrator Control
                </h2>

                <p>
                    You are logged in as an administrator.
                    Administrator tools and user management
                    will be connected here.
                </p>


                <span class="admin-badge">
                    ADMINISTRATOR ACCESS
                </span>

                <a href="recorder-equipment.php"
                   style="display:inline-block;margin-top:12px;padding:10px 14px;background:#111;color:#fff;text-decoration:none;font-size:9px;font-weight:bold;letter-spacing:1px;">
                    ⚙ RECORDER EQUIPMENT SETTINGS</a>

                <a href="gauge-equipment.php" style="display:inline-block;margin-top:12px;padding:10px 14px;background:#111;color:#fff;text-decoration:none;font-size:9px;font-weight:bold;letter-spacing:1px;">⚙ GAUGE EQUIPMENT SETTINGS</a>

                <a href="nuprc-settings.php" style="display:inline-block;margin-top:12px;margin-left:8px;padding:10px 14px;background:#c90000;color:#fff;text-decoration:none;font-size:9px;font-weight:bold;letter-spacing:1px;">⚙ NUPRC / OGISP SETTINGS</a>

            </section>

        <?php endif; ?>


    </main>

</div>


<!-- =========================================================
     SIDEBAR JAVASCRIPT
========================================================= -->

<script>

const sidebar =
    document.getElementById("sidebar");

const mobileMenu =
    document.getElementById("mobileMenu");

const sidebarOverlay =
    document.getElementById("sidebarOverlay");


function openSidebar() {

    sidebar.classList.add("open");

    sidebarOverlay.classList.add("open");
}


function closeSidebar() {

    sidebar.classList.remove("open");

    sidebarOverlay.classList.remove("open");
}


if (mobileMenu) {

    mobileMenu.addEventListener(
        "click",
        function () {

            if (
                sidebar.classList.contains("open")
            ) {

                closeSidebar();

            } else {

                openSidebar();

            }

        }
    );

}


if (sidebarOverlay) {

    sidebarOverlay.addEventListener(
        "click",
        closeSidebar
    );

}


/* Close sidebar after selecting a page on mobile */

document
    .querySelectorAll(".nav-item")
    .forEach(function(item) {

        item.addEventListener(
            "click",
            function() {

                if (
                    window.innerWidth <= 750
                ) {

                    closeSidebar();

                }

            }
        );

    });

</script>


</body>

</html>
