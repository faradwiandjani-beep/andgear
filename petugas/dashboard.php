<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

function hitungData($koneksi, $sql) {
    $hasil = mysqli_query($koneksi, $sql);

    if ($hasil) {
        $data = mysqli_fetch_assoc($hasil);
        return (int)$data['total'];
    }

    return 0;
}

$totalMenunggu = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'menunggu'"
);

$totalDisetujui = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'disetujui'"
);

$totalDipinjam = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dipinjam'"
);

$totalPengembalian = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM pengembalian WHERE status = 'diajukan'"
);

$queryTerbaru = mysqli_query($koneksi, "
    SELECT p.kode_peminjaman, p.tujuan_pendakian,
           p.tanggal_pinjam, p.tanggal_rencana_kembali,
           p.status, u.nama AS nama_peminjam
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id_user
    ORDER BY p.created_at DESC
    LIMIT 5
");

$namaPetugas = 'Petugas';

if (isset($_SESSION['nama'])) {
    $namaPetugas = $_SESSION['nama'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Petugas - ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="d-flex min-vh-100">

    <aside class="bg-white border-end p-3"
           style="width:260px; flex-shrink:0;">

        <h4 class="text-primary fw-bold mb-1">ANDGEAR</h4>
        <p class="text-secondary small mb-4">Panel Petugas</p>

        <div class="nav flex-column gap-2">
            <a href="dashboard.php"
               class="nav-link active bg-primary text-white rounded">
                Dashboard
            </a>

            <a href="peminjaman.php" class="nav-link text-dark">
                Data Peminjaman
            </a>

            <a href="pengembalian.php" class="nav-link text-dark">
                Data Pengembalian
            </a>
        </div>

        <hr>

        <a href="../logout.php"
           class="btn btn-outline-danger w-100"
           onclick="return confirm('Yakin ingin keluar?')">
            Logout
        </a>
    </aside>

    <main class="flex-grow-1 p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Dashboard Petugas</h3>
                <p class="text-secondary mb-0">
                    Selamat datang, <?= e($namaPetugas); ?>!
                </p>
            </div>

            <span class="badge text-bg-primary p-2">Petugas ANDGEAR</span>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Peminjaman Menunggu</p>
                        <h2 class="fw-bold text-warning"><?= $totalMenunggu; ?></h2>
                        <a href="peminjaman.php" class="small text-decoration-none">Lihat peminjaman</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Peminjaman Disetujui</p>
                        <h2 class="fw-bold text-success"><?= $totalDisetujui; ?></h2>
                        <a href="peminjaman.php" class="small text-decoration-none">Lihat peminjaman</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Alat Sedang Dipinjam</p>
                        <h2 class="fw-bold text-primary"><?= $totalDipinjam; ?></h2>
                        <a href="peminjaman.php" class="small text-decoration-none">Lihat peminjaman</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-2">Pengajuan Pengembalian</p>
                        <h2 class="fw-bold text-danger"><?= $totalPengembalian; ?></h2>
                        <a href="pengembalian.php" class="small text-decoration-none">Lihat pengembalian</a>
                    </div>
                </div>
            </div>

        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Menu Cepat</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="peminjaman.php" class="btn btn-primary">Kelola Peminjaman</a>
                    <a href="pengembalian.php" class="btn btn-outline-primary">Kelola Pengembalian</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Peminjaman Terbaru</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>No.</th>
                                <th>Kode Peminjaman</th>
                                <th>Nama Peminjam</th>
                                <th>Tujuan</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($queryTerbaru && mysqli_num_rows($queryTerbaru) > 0) {
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($queryTerbaru)) {
                                $warna = 'warning';

                                if ($row['status'] == 'disetujui') {
                                    $warna = 'success';
                                } elseif ($row['status'] == 'ditolak') {
                                    $warna = 'danger';
                                } elseif ($row['status'] == 'selesai') {
                                    $warna = 'secondary';
                                }
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= e($row['kode_peminjaman']); ?></td>
                                <td><?= e($row['nama_peminjam']); ?></td>
                                <td><?= e($row['tujuan_pendakian']); ?></td>
                                <td><?= e($row['tanggal_pinjam']); ?></td>
                                <td><?= e($row['tanggal_rencana_kembali']); ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $warna; ?>">
                                        <?= e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">
                                    Belum ada data peminjaman.
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <a href="peminjaman.php" class="btn btn-outline-primary btn-sm">
                    Lihat Semua Peminjaman
                </a>
            </div>
        </div>

    </main>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>