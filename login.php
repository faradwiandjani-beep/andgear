<?php
session_start();
require_once "koneksi.php";

$error = "";

$redirect = $_GET["redirect"] ?? $_POST["redirect"] ?? "";

/* Validasi tujuan redirect */
$redirectValid = [
    "peminjam/peminjaman.php",
    "peminjam/riwayat.php",
    "peminjam/keranjang.php"
];

if ($redirect !== "" && !in_array($redirect, $redirectValid, true)) {
    $redirect = "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT * FROM user WHERE username = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($hasil);
        mysqli_stmt_close($stmt);

        if (
            $user &&
            (
                password_verify($password, $user["password"]) ||
                $password === $user["password"]
            )
        ) {
            $_SESSION["id_user"] = (int)$user["id_user"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            /* CATAT AKTIVITAS LOGIN */
            $idUser = (int)$user["id_user"];
            $aktivitas = "Login ke sistem ANDGEAR sebagai " . $user["role"];

            $stmtLog = mysqli_prepare(
                $koneksi,
                "INSERT INTO log_aktivitas (id_user, aktivitas)
                 VALUES (?, ?)"
            );

            if ($stmtLog) {
                mysqli_stmt_bind_param(
                    $stmtLog,
                    "is",
                    $idUser,
                    $aktivitas
                );

                mysqli_stmt_execute($stmtLog);
                mysqli_stmt_close($stmtLog);
            }

            /* Arahkan pengguna sesuai role */
            if ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
                exit;
            } elseif ($user["role"] === "petugas") {
                header("Location: petugas/dashboard.php");
                exit;
            } elseif ($user["role"] === "peminjam") {
                if ($redirect !== "") {
                    header("Location: " . $redirect);
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                session_unset();
                $error = "Role akun tidak dikenali!";
            }
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | ANDGEAR</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card border-0 shadow-sm rounded-4 p-4 w-100"
         style="max-width:430px;">

        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">ANDGEAR</h2>
            <p class="text-secondary">
                Login Sistem Penyewaan Alat Outdoor
            </p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="redirect"
                   value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>

            <div class="text-center mt-3">
                <span class="text-secondary">Belum punya akun?</span>
                <a href="daftar.php<?= $redirect !== '' ? '?redirect=' . urlencode($redirect) : ''; ?>"
                   class="text-decoration-none fw-semibold">
                    Daftar sekarang
                </a>
            </div>

            <a href="index.php" class="btn btn-outline-secondary w-100 mt-3">
                Kembali ke Beranda
            </a>
        </form>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>