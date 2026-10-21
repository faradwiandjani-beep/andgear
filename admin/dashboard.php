<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit;
}

// Menghitung jumlah data
function hitungData($koneksi, $sql)
{
    $query = mysqli_query($koneksi, $sql);

    if ($query) {
        $data = mysqli_fetch_row($query);
        return (int) ($data[0] ?? 0);
    }

    return 0;
}

$total_alat = hitungData(
    $koneksi,
    "SELECT COUNT(*) FROM alat"
);

$total_kategori = hitungData(
    $koneksi,
    "SELECT COUNT(*) FROM kategori"
);

$total_peminjaman = hitungData(
    $koneksi,
    "SELECT COUNT(*) FROM peminjaman"
);

$total_pengembalian = hitungData(
    $koneksi,
    "SELECT COUNT(*) FROM peminjaman WHERE status = 'dikembalikan'"
);

// Mengambil nama admin
$nama_admin = $_SESSION["nama"] ?? "Admin";

// Transaksi terbaru
$transaksi = mysqli_query(
    $koneksi,
    "SELECT * FROM peminjaman ORDER BY id_peminjaman DESC LIMIT 5"
);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin | ANDGEAR</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-dark bg-dark px-3">
        <a href="dashboard.php" class="navbar-brand fw-bold">
            ANDGEAR
        </a>

        <div class="d-flex align-items-center gap-3 text-white">
            <span>Admin: <?= htmlspecialchars($nama_admin); ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"
                onclick="return confirm('Yakin ingin logout?')">
                Logout
            </a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <aside class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">
                <h6 class="text-secondary mb-3">MENU UTAMA</h6>

                <div class="nav flex-column gap-2">
                    <a href="dashboard.php"
                        class="nav-link active bg-primary text-white rounded">
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

                    <a href="log_aktivitas.php" class="nav-link text-dark">
                        Log Aktivitas
                    </a>

                    <hr>

                    <a href="../index.php" class="nav-link text-dark">
                        Lihat Halaman Utama
                    </a>
                </div>
            </aside>

            <!-- KONTEN -->
            <main class="col-md-9 col-lg-10 p-4">

                <div class="mb-4">
                    <h2 class="fw-bold">Dashboard Admin</h2>
                    <p class="text-secondary">
                        Selamat datang di sistem penyewaan alat outdoor ANDGEAR.
                    </p>
                </div>

                <!-- KARTU STATISTIK -->
                <div class="row g-3">

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-secondary mb-2">Total Alat</p>
                                <h2 class="fw-bold text-primary">
                                    <?= $total_alat; ?>
                                </h2>
                                <a href="alat.php"
                                    class="small text-decoration-none">
                                    Lihat data alat →
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-secondary mb-2">Total Kategori</p>
                                <h2 class="fw-bold text-success">
                                    <?= $total_kategori; ?>
                                </h2>
                                <a href="kategori.php"
                                    class="small text-decoration-none">
                                    Lihat kategori →
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-secondary mb-2">Total Peminjaman</p>
                                <h2 class="fw-bold text-warning">
                                    <?= $total_peminjaman; ?>
                                </h2>
                                <a href="peminjaman.php"
                                    class="small text-decoration-none">
                                    Lihat peminjaman →
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-secondary mb-2">Total Pengembalian</p>
                                <h2 class="fw-bold text-info">
                                    <?= $total_pengembalian; ?>
                                </h2>
                                <a href="pengembalian.php"
                                    class="small text-decoration-none">
                                    Lihat pengembalian →
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TABEL PEMINJAMAN TERBARU -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Peminjaman Terbaru</h5>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Peminjaman</th>
                                        <th>Informasi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if ($transaksi && mysqli_num_rows($transaksi) > 0): ?>
                                        <?php $no = 1; ?>
                                        <?php while ($row = mysqli_fetch_assoc($transaksi)): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td>
                                                    <?= htmlspecialchars(
                                                        (string) ($row["id_peminjaman"] ?? "-")
                                                    ); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        Data peminjaman
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3"
                                                class="text-center text-secondary py-4">
                                                Belum ada data peminjaman.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <a href="peminjaman.php"
                            class="btn btn-primary btn-sm">
                            Lihat Semua Peminjaman
                        </a>
                    </div>
                </div>

                <footer class="text-center text-secondary mt-4 small">
                    &copy; <?= date("Y"); ?> ANDGEAR — Penyewaan Alat Outdoor
                </footer>

            </main>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>