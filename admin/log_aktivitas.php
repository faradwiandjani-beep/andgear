<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

/* Pencarian aktivitas */
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $kataCari = "%" . $cari . "%";

    $stmt = mysqli_prepare($koneksi, "
        SELECT
            l.id,
            l.id_user,
            u.nama AS nama_user,
            l.aktivitas,
            l.waktu
        FROM log_aktivitas l
        LEFT JOIN user u ON u.id_user = l.id_user
        WHERE l.aktivitas LIKE ?
           OR u.nama LIKE ?
           OR CAST(l.id_user AS CHAR) LIKE ?
        ORDER BY l.waktu DESC, l.id DESC
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $kataCari,
        $kataCari,
        $kataCari
    );

    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
} else {
    $query = mysqli_query($koneksi, "
        SELECT
            l.id,
            l.id_user,
            u.nama AS nama_user,
            l.aktivitas,
            l.waktu
        FROM log_aktivitas l
        LEFT JOIN user u ON u.id_user = l.id_user
        ORDER BY l.waktu DESC, l.id DESC
    ");
}

/* Hitung total data aktivitas */
$totalAktivitas = 0;

if ($query) {
    $totalAktivitas = mysqli_num_rows($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Aktivitas - ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="d-flex min-vh-100">

    <!-- SIDEBAR -->
    <aside class="bg-white border-end p-3"
           style="width:260px; flex-shrink:0;">

        <h4 class="text-primary fw-bold mb-1">ANDGEAR</h4>
        <p class="text-secondary small mb-4">Panel Admin</p>

        <div class="nav flex-column gap-2">
            <a href="dashboard.php" class="nav-link text-dark">
                Dashboard
            </a>

            <a href="user.php" class="nav-link text-dark">
                Data User
            </a>

            <a href="alat.php" class="nav-link text-dark">
                Data Alat
            </a>

            <a href="kategori.php" class="nav-link text-dark">
                Data Kategori
            </a>

            <a href="peminjaman.php" class="nav-link text-dark">
                Data Peminjaman
            </a>

            <a href="pengembalian.php" class="nav-link text-dark">
                Data Pengembalian
            </a>

            <a href="log_aktivitas.php"
               class="nav-link active bg-primary text-white rounded">
                Log Aktivitas
            </a>
        </div>

        <hr>

        <a href="../logout.php"
           class="btn btn-outline-danger w-100"
           onclick="return confirm('Yakin ingin keluar?')">
            Logout
        </a>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="flex-grow-1 p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-1">
            <div>
                <h3 class="fw-bold mb-1">Log Aktivitas</h3>
                <p class="text-secondary mb-0">
                    Riwayat aktivitas pengguna sistem ANDGEAR.
                </p>
            </div>

            <span class="badge bg-primary fs-6">
                <?= (int)$totalAktivitas; ?> Aktivitas
            </span>
        </div>

        <!-- PENCARIAN -->
        <div class="card border-0 shadow-sm mt-4 mb-4">
            <div class="card-body">
                <form method="get" class="row g-2">
                    <div class="col-md-10">
                        <input
                            type="text"
                            name="cari"
                            class="form-control"
                            placeholder="Cari nama pengguna, ID user, atau aktivitas..."
                            value="<?= e($cari); ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Cari
                        </button>

                        <a href="log_aktivitas.php"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL AKTIVITAS -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">

                        <thead class="table-primary">
                            <tr>
                                <th>No.</th>
                                <th>ID User</th>
                                <th>Nama Pengguna</th>
                                <th>Aktivitas</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0): ?>

                            <?php $no = 1; ?>

                            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= (int)$row['id_user']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($row['nama_user'] ?? 'Pengguna tidak ditemukan'); ?>
                                    </td>

                                    <td>
                                        <span class="text-dark">
                                            <?= e($row['aktivitas']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="text-secondary">
                                            <?= e($row['waktu']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="5"
                                    class="text-center text-secondary py-5">
                                    <div class="mb-2">
                                        <strong>
                                            <?= $cari !== ''
                                                ? 'Aktivitas tidak ditemukan.'
                                                : 'Belum ada aktivitas yang tercatat.'; ?>
                                        </strong>
                                    </div>

                                    <small>
                                        Aktivitas akan muncul setelah sistem
                                        menyimpan catatan ke database.
                                    </small>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

        <p class="text-secondary small mt-3">
            Log aktivitas ditampilkan dari tabel
            <code>log_aktivitas</code> dan diurutkan dari yang terbaru.
        </p>

    </main>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>