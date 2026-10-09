<?php

session_start();

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "technician") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}


/*
|--------------------------------------------------------------------------
| LOAD USERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            username,
            full_name,
            role,
            status,
            created_at
        FROM users
        ORDER BY id DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $users = [];

    $databaseError = $e->getMessage();
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Management | CMS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f4f4;

            color: #111;

        }


        /* =========================================
           HEADER
        ========================================= */

        .header {

            min-height: 75px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            background: #fff;

            border-bottom: 4px solid #c90000;

            box-shadow:
                0 2px 10px rgba(0,0,0,.08);

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand-logo {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 3px solid #c90000;

            border-radius: 50%;

            color: #111;

            font-size: 11px;

            font-weight: bold;

        }


        .brand-name strong {

            display: block;

            font-size: 17px;

            letter-spacing: 3px;

        }


        .brand-name small {

            display: block;

            margin-top: 3px;

            color: #777;

            font-size: 7px;

            letter-spacing: 1px;

        }


        .back-button {

            display: inline-block;

            padding: 11px 16px;

            background: #111;

            color: #fff;

            text-decoration: none;

            font-size: 9px;

            font-weight: bold;

            letter-spacing: 1px;

        }


        .back-button:hover {

            background: #c90000;

        }


        /* =========================================
           PAGE
        ========================================= */

        .page {

            width: 100%;

            max-width: 1200px;

            margin: auto;

            padding: 30px 20px 50px;

        }


        .page-heading {

            margin-bottom: 25px;

        }


        .page-heading span {

            color: #c90000;

            font-size: 8px;

            letter-spacing: 2px;

            font-weight: bold;

        }


        .page-heading h1 {

            margin: 7px 0;

            font-size: 28px;

        }


        .page-heading p {

            margin: 0;

            color: #666;

            font-size: 11px;

        }


        /* =========================================
           ADD USER PANEL
        ========================================= */

        .panel {

            margin-bottom: 25px;

            padding: 25px;

            background: #fff;

            border: 1px solid #ddd;

            border-top: 5px solid #c90000;

            box-shadow:
                0 4px 15px rgba(0,0,0,.06);

        }


        .panel-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;

        }


        .panel-heading h2 {

            margin: 0;

            font-size: 17px;

        }


        .panel-heading span {

            color: #777;

            font-size: 9px;

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;

        }


        .field {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .field label {

            color: #333;

            font-size: 9px;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: .5px;

        }


        .field input,
        .field select {

            width: 100%;

            padding: 13px;

            border: 1px solid #ccc;

            background: #fafafa;

            color: #111;

            outline: none;

            font-family: inherit;

        }


        .field input:focus,
        .field select:focus {

            border-color: #c90000;

            background: #fff;

        }


        .form-actions {

            margin-top: 20px;

            display: flex;

            justify-content: flex-end;

        }


        .add-button {

            padding: 13px 22px;

            border: 2px solid #c90000;

            background: #c90000;

            color: #fff;

            cursor: pointer;

            font-size: 9px;

            font-weight: bold;

            letter-spacing: 1px;

        }


        .add-button:hover {

            background: #111;

            border-color: #111;

        }


        /* =========================================
           USER TABLE
        ========================================= */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            min-width: 700px;

            border-collapse: collapse;

        }


        th {

            padding: 14px;

            text-align: left;

            background: #111;

            color: #fff;

            font-size: 8px;

            letter-spacing: 1px;

        }


        td {

            padding: 14px;

            border-bottom: 1px solid #e2e2e2;

            color: #444;

            font-size: 10px;

            background: #fff;

        }


        tr:hover td {

            background: #fafafa;

        }


        .role {

            display: inline-block;

            padding: 5px 8px;

            background: #111;

            color: #fff;

            font-size: 7px;

            font-weight: bold;

            text-transform: uppercase;

        }


        .status {

            display: inline-block;

            padding: 5px 8px;

            font-size: 7px;

            font-weight: bold;

            text-transform: uppercase;

        }


        .status.active {

            color: #08752f;

            background: #e8f8ee;

        }


        .status.inactive {

            color: #c90000;

            background: #ffeaea;

        }


        .empty {

            padding: 30px;

            text-align: center;

            color: #777;

            font-size: 11px;

            background: #fff;

        }


        .error {

            padding: 15px;

            margin-bottom: 20px;

            color: #a00000;

            background: #ffeaea;

            border-left: 4px solid #c90000;

            font-size: 10px;

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 650px) {

            .header {

                padding: 0 15px;

            }


            .page {

                padding: 20px 15px 40px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .page-heading h1 {

                font-size: 23px;

            }


            .brand-name {

                display: none;

            }

        }

    </style>

</head>


<body>


<header class="header">

    <div class="brand">

        <div class="brand-logo">
            CAL
        </div>

        <div class="brand-name">

            <strong>
                CMS
            </strong>

            <small>
                CALIBRATION MANAGEMENT SYSTEM
            </small>

        </div>

    </div>


    <a
        href="dashboard.php"
        class="back-button"
    >
        ← DASHBOARD
    </a>

</header>


<main class="page">


    <div class="page-heading">

        <span>
            ADMINISTRATION
        </span>

        <h1>
            User Management
        </h1>

        <p>
            Create and manage users who can access the
            Calibration Management System.
        </p>

    </div>


    <?php if (isset($databaseError)): ?>

        <div class="error">

            Database error:

            <?= htmlspecialchars($databaseError) ?>

        </div>

    <?php endif; ?>


    <!-- ADD USER -->

    <section class="panel">

        <div class="panel-heading">

            <h2>
                Add New User
            </h2>

            <span>
                ADMIN ONLY
            </span>

        </div>


        <form
            method="POST"
            action="#"
        >

            <div class="form-grid">


                <div class="field">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter full name"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Enter username"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Temporary Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter password"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Role
                    </label>

                    <select name="role">

                        <option value="technician">
                            Technician
                        </option>

                        <option value="admin">
                            Administrator
                        </option>

                    </select>

                </div>


            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="add-button"
                >
                    + CREATE USER
                </button>

            </div>

        </form>

    </section>


    <!-- USERS -->

    <section class="panel">

        <div class="panel-heading">

            <h2>
                System Users
            </h2>

            <span>
                <?= count($users) ?> USER(S)
            </span>

        </div>


        <div class="table-wrapper">

            <?php if (count($users) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                USERNAME
                            </th>

                            <th>
                                FULL NAME
                            </th>

                            <th>
                                ROLE
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th>
                                CREATED
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($users as $account): ?>

                        <tr>

                            <td>
                                <?= (int)$account["id"] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($account["username"]) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($account["full_name"]) ?>
                            </td>

                            <td>

                                <span class="role">

                                    <?= htmlspecialchars($account["role"]) ?>

                                </span>

                            </td>

                            <td>

                                <span
                                    class="status
                                    <?= strtolower($account["status"]) === "active"
                                        ? "active"
                                        : "inactive"
                                    ?>"
                                >

                                    <?= htmlspecialchars($account["status"]) ?>

                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars($account["created_at"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    No users found.

                </div>

            <?php endif; ?>

        </div>

    </section>


</main>


</body>

</html>