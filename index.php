<?php
session_start();
require_once "koneksi.php";

// Ambil kategori alat
$query = mysqli_query($koneksi, "SELECT * FROM kategori");

// Periksa apakah pengguna adalah peminjam yang sudah login
$peminjamLogin = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'peminjam'
);

// Tentukan link riwayat
$linkRiwayat = $peminjamLogin
    ? "peminjam/riwayat.php"
    : "login.php?redirect=" . urlencode("peminjam/riwayat.php");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ANDGEAR - Penyewaan Alat Pendakian</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark"
     style="background-color:#183D32;">

    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            AND<span style="color:#D99A52;">GEAR</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu"
                aria-controls="menu"
                aria-expanded="false"
                aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#kategori">
                        Kategori Alat
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= htmlspecialchars($linkRiwayat, ENT_QUOTES, 'UTF-8'); ?>">
                        Riwayat Sewa
                    </a>
                </li>

                <?php if ($peminjamLogin) { ?>

                    <li class="nav-item">
                        <span class="nav-link">
                            Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Peminjam', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a href="logout.php"
                           class="btn btn-outline-light btn-sm px-3"
                           onclick="return confirm('Yakin ingin logout?')">
                            Logout
                        </a>
                    </li>

                <?php } else { ?>

                    <li class="nav-item ms-lg-2">
                        <a href="login.php"
                           class="btn btn-light btn-sm px-4">
                            Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="daftar.php"
                           class="btn btn-sm px-4"
                           style="background-color:#D99A52;color:#183D32;">
                            Daftar
                        </a>
                    </li>

                <?php } ?>

            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section style="background-color:#F7F4EA;">
    <div class="container py-5">
        <div class="row align-items-center py-lg-4">

            <div class="col-lg-7">
                <span class="badge rounded-pill mb-3 px-3 py-2"
                      style="background-color:#DCE8D5;color:#183D32;">
                    PERLENGKAPAN PETUALANGANMU
                </span>

                <h1 class="display-4 fw-bold mb-3"
                    style="color:#183D32;">
                    Mulai Petualanganmu Bersama ANDGEAR
                </h1>

                <p class="fs-5 text-secondary mb-4">
                    Jelajahi alam tanpa harus membeli semua perlengkapan.
                    Temukan dan sewa peralatan pendakian yang kamu butuhkan
                    untuk perjalanan yang lebih nyaman.
                </p>

                <a href="#kategori"
                   class="btn btn-lg px-4 me-2 mb-2"
                   style="background-color:#183D32;color:white;">
                    Jelajahi Alat
                </a>

                <a href="<?= htmlspecialchars($linkRiwayat, ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-outline-success btn-lg px-4 mb-2">
                    <span class="me-1">↗</span> Riwayat Sewa
                </a>

                <div class="d-flex flex-wrap gap-4 mt-4 text-secondary small">
                    <span>🏕️ Peralatan Outdoor</span>
                    <span>⛰️ Siap Bertualang</span>
                    <span>✓ Pengajuan Mudah</span>
                </div>
            </div>

            <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="rounded-4 p-4 shadow-sm text-center"
                     style="background-color:#183D32;color:white;">

                    <div class="display-1 mb-3">🏕️</div>

                    <h2 class="fw-bold">Explore More</h2>

                    <p style="color:#DCE8D5;">
                        Your Adventure Starts Here
                    </p>

                    <hr style="border-color:#DCE8D5;">

                    <div class="row text-center">
                        <div class="col-6">
                            <div class="fs-3">🎒</div>
                            <small>Gear Rental</small>
                        </div>

                        <div class="col-6">
                            <div class="fs-3">⛰️</div>
                            <small>Outdoor Adventure</small>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- KATEGORI -->
<section class="container py-5" id="kategori">

    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
        <div>
            <span class="small fw-bold"
                  style="color:#527B58;">
                TEMUKAN PERLENGKAPAN
            </span>

            <h2 class="fw-bold mt-1" style="color:#183D32;">
                Kategori Peralatan
            </h2>

            <p class="text-secondary mb-0">
                Pilih kategori untuk melihat alat yang tersedia.
            </p>
        </div>

        <a href="<?= htmlspecialchars($linkRiwayat, ENT_QUOTES, 'UTF-8'); ?>"
           class="btn btn-outline-success">
            Lihat Riwayat →
        </a>
    </div>

    <div class="row g-3">

        <?php if ($query && mysqli_num_rows($query) > 0) { ?>

            <?php while ($kategori = mysqli_fetch_assoc($query)) { ?>

                <div class="col-6 col-md-4 col-lg-3">

                    <a href="peminjam/alat.php?id_kategori=<?= (int)$kategori['id_kategori']; ?>"
                       class="text-decoration-none text-dark">

                        <div class="card h-100 border-0 shadow-sm rounded-4">

                            <div class="card-body p-4">

                                <div class="rounded-3 d-flex align-items-center justify-content-center mb-3"
                                     style="height:75px;background-color:#DCE8D5;">

                                    <span style="font-size:38px;">🏕️</span>

                                </div>

                                <h5 class="fw-bold"
                                    style="color:#183D32;">
                                    <?= htmlspecialchars(
                                        $kategori['nama_kategori'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </h5>

                                <p class="text-secondary small mb-3">
                                    <?php
                                    $deskripsi = trim($kategori['deskripsi'] ?? '');

                                    echo htmlspecialchars(
                                        $deskripsi !== ''
                                            ? $deskripsi
                                            : 'Temukan perlengkapan untuk petualanganmu.',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </p>

                                <span class="fw-semibold small"
                                      style="color:#527B58;">
                                    Lihat Peralatan →
                                </span>

                            </div>
                        </div>

                    </a>

                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="col-12">
                <div class="alert alert-info">
                    Belum ada kategori alat yang tersedia.
                </div>
            </div>

        <?php } ?>

    </div>
</section>

<!-- RIWAYAT SEWA -->
<section class="py-5" style="background-color:#DCE8D5;">
    <div class="container">

        <div class="row align-items-center g-3">

            <div class="col-lg-8">
                <h3 class="fw-bold" style="color:#183D32;">
                    Mau Cek Penyewaanmu?
                </h3>

                <p class="mb-0 text-secondary">
                    Lihat kembali alat yang pernah kamu ajukan,
                    tanggal peminjaman, dan status terbaru pengajuanmu.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <a href="<?= htmlspecialchars($linkRiwayat, ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-lg px-4"
                   style="background-color:#183D32;color:white;">
                    Buka Riwayat Sewa →
                </a>
            </div>

        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="py-4 text-white"
        style="background-color:#183D32;">

    <div class="container text-center">
        <a href="index.php"
           class="text-white text-decoration-none fw-bold fs-5">
            AND<span style="color:#D99A52;">GEAR</span>
        </a>

        <p class="small mt-2 mb-1">
            Teman petualangan dan penyewaan alat pendakianmu.
        </p>

        <p class="small mb-0" style="color:#DCE8D5;">
            &copy; <?= date('Y'); ?> ANDGEAR. All rights reserved.
        </p>
    </div>

</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>