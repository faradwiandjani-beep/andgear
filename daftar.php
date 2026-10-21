<?php
session_start();
require_once "koneksi.php";

$error = "";

// Ambil tujuan setelah daftar
$redirect = $_GET["redirect"] ?? $_POST["redirect"] ?? "";

// Validasi redirect agar hanya menuju formulir penyewaan
if (
    $redirect !== "" &&
    (
        strpos($redirect, "peminjam/peminjaman.php") !== 0 ||
        strpos($redirect, "//") !== false ||
        strpos($redirect, "\\") !== false
    )
) {
    $redirect = "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";
    $no_hp = trim($_POST["no_hp"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");

    if (
        $nama == "" || $username == "" || $password == "" ||
        $konfirmasi == "" || $no_hp == "" || $alamat == ""
    ) {
        $error = "Semua kolom wajib diisi!";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Periksa username
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user FROM user WHERE username = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $ada = mysqli_fetch_assoc($hasil);
        mysqli_stmt_close($stmt);

        if ($ada) {
            $error = "Username sudah digunakan!";
        } else {
            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "peminjam";

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO user
                (nama, username, password, role, no_hp, alamat)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssssss",
                $nama,
                $username,
                $password_hash,
                $role,
                $no_hp,
                $alamat
            );

            if (mysqli_stmt_execute($stmt)) {
                $id_user = mysqli_insert_id($koneksi);
                mysqli_stmt_close($stmt);

                // Langsung login setelah pendaftaran berhasil
                session_regenerate_id(true);

                $_SESSION["id_user"] = $id_user;
                $_SESSION["nama"] = $nama;
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $role;

                // Menuju formulir alat yang dipilih
                if ($redirect !== "") {
                    header("Location: " . $redirect);
                } else {
                    header("Location: index.php");
                }

                exit;
            } else {
                $error = "Pendaftaran gagal. Silakan coba lagi.";
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Akun | ANDGEAR</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">

    <div class="card border-0 shadow-sm rounded-4 p-4 w-100"
         style="max-width: 480px;">

        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">ANDGEAR</h2>

            <p class="text-secondary">
                Buat akun untuk mulai menyewa alat outdoor
            </p>
        </div>

        <?php if ($error != "") { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <form method="POST" action="daftar.php">

            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>

                <input
                    type="text"
                    name="nama"
                    class="form-control"
                    placeholder="Masukkan nama lengkap"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Buat username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor HP</label>

                <input
                    type="tel"
                    name="no_hp"
                    class="form-control"
                    placeholder="Masukkan nomor HP"
                    value="<?= htmlspecialchars($_POST['no_hp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Alamat</label>

                <textarea
                    name="alamat"
                    class="form-control"
                    rows="2"
                    placeholder="Masukkan alamat lengkap"
                    required><?= htmlspecialchars($_POST['alamat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimal 6 karakter"
                    minlength="6"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>

                <input
                    type="password"
                    name="konfirmasi"
                    class="form-control"
                    placeholder="Ulangi password"
                    minlength="6"
                    required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Daftar dan Lanjutkan
            </button>

            <p class="text-center mt-3 mb-0">
                Sudah punya akun?

                <a
                    href="login.php<?= $redirect !== '' ? '?redirect=' . urlencode($redirect) : ''; ?>"
                    class="text-decoration-none fw-semibold">
                    Login
                </a>
            </p>

            <a href="index.php"
               class="btn btn-outline-secondary w-100 mt-3">
                Kembali ke Beranda
            </a>

        </form>

    </div>

</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>