<?php
session_start();
require_once "../koneksi.php";

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

// Inisialisasi keranjang
if (!isset($_SESSION['keranjang_andgear']) || !is_array($_SESSION['keranjang_andgear'])) {
    $_SESSION['keranjang_andgear'] = [];
}

$pesan = "";

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tambah_keranjang'])) {

    $idAlat = (int)($_POST['id_alat'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 1);

    if ($jumlah < 1) {
        $jumlah = 1;
    }

    $stmtCek = mysqli_prepare(
        $koneksi,
        "SELECT id_alat, nama_alat, stok
         FROM alat
         WHERE id_alat = ?"
    );

    mysqli_stmt_bind_param($stmtCek, "i", $idAlat);
    mysqli_stmt_execute($stmtCek);
    $hasilCek = mysqli_stmt_get_result($stmtCek);
    $dataAlat = mysqli_fetch_assoc($hasilCek);

    if (!$dataAlat) {
        $_SESSION['pesan_keranjang'] = "Alat tidak ditemukan.";
    } elseif ((int)$dataAlat['stok'] < 1) {
        $_SESSION['pesan_keranjang'] = "Stok alat sedang habis.";
    } else {
        $jumlahLama = (int)(
            $_SESSION['keranjang_andgear'][$idAlat] ?? 0
        );

        $jumlahBaru = $jumlahLama + $jumlah;
        $stok = (int)$dataAlat['stok'];

        if ($jumlahBaru > $stok) {
            $_SESSION['pesan_keranjang'] =
                "Jumlah " . $dataAlat['nama_alat']
                . " melebihi stok tersedia (" . $stok . " unit).";
        } else {
            $_SESSION['keranjang_andgear'][$idAlat] = $jumlahBaru;

            $_SESSION['pesan_keranjang'] =
                $dataAlat['nama_alat']
                . " berhasil ditambahkan ke keranjang.";
        }
    }

    header("Location: keranjang.php");
    exit;
}

// Ambil ID kategori
$idKategori = isset($_GET['id_kategori'])
    ? (int)$_GET['id_kategori']
    : 0;

// Ambil nama kategori
$stmtKategori = mysqli_prepare(
    $koneksi,
    "SELECT nama_kategori FROM kategori WHERE id_kategori = ?"
);

mysqli_stmt_bind_param($stmtKategori, "i", $idKategori);
mysqli_stmt_execute($stmtKategori);
$hasilKategori = mysqli_stmt_get_result($stmtKategori);
$kategori = mysqli_fetch_assoc($hasilKategori);

if (!$kategori) {
    header("Location: ../index.php");
    exit;
}

// Ambil alat berdasarkan kategori
$stmtAlat = mysqli_prepare(
    $koneksi,
    "SELECT * FROM alat
     WHERE id_kategori = ?
     ORDER BY nama_alat ASC"
);

mysqli_stmt_bind_param($stmtAlat, "i", $idKategori);
mysqli_stmt_execute($stmtAlat);
$queryAlat = mysqli_stmt_get_result($stmtAlat);

// Status login
$sudahLogin = isset($_SESSION['role'])
    && $_SESSION['role'] === 'peminjam';

$nama = $_SESSION['nama'] ?? 'Pengunjung';

// Hitung jumlah semua unit dalam keranjang
$totalKeranjang = array_sum($_SESSION['keranjang_andgear']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($kategori['nama_kategori']); ?> - ANDGEAR</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container">

        <a href="../index.php" class="navbar-brand text-primary fw-bold">
            ANDGEAR
        </a>

        <div class="d-flex align-items-center gap-2 flex-wrap">

            <a href="keranjang.php" class="btn btn-outline-primary btn-sm">
                🛒 Keranjang
                <span class="badge bg-primary">
                    <?= (int)$totalKeranjang; ?>
                </span>
            </a>

            <?php if ($sudahLogin) { ?>

                <span class="text-secondary small">
                    Halo, <?= e($nama); ?>
                </span>

                <a href="riwayat.php" class="btn btn-outline-secondary btn-sm">
                    Riwayat Sewa
                </a>

                <a href="../logout.php"
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('Yakin ingin keluar?')">
                    Logout
                </a>

            <?php } else { ?>

                <a href="../login.php"
                   class="btn btn-primary btn-sm px-3">
                    Login
                </a>

                <a href="../daftar.php"
                   class="btn btn-outline-primary btn-sm">
                    Daftar
                </a>

            <?php } ?>

        </div>
    </div>
</nav>

<!-- DAFTAR ALAT -->
<main class="container py-4">

    <a href="../index.php#kategori"
       class="btn btn-outline-primary btn-sm mb-3">
        &larr; Kembali ke Kategori
    </a>

    <div class="mb-4">
        <h3 class="fw-bold mb-1">
            <?= e($kategori['nama_kategori']); ?>
        </h3>

        <p class="text-secondary mb-0">
            Pilih perlengkapan yang kamu butuhkan, masukkan ke keranjang,
            lalu ajukan penyewaan.
        </p>
    </div>

    <div class="row g-3">

        <?php if (mysqli_num_rows($queryAlat) > 0) { ?>

            <?php while ($alat = mysqli_fetch_assoc($queryAlat)) { ?>

                <?php
                $idAlatTampil = (int)$alat['id_alat'];
                $jumlahDiKeranjang = (int)(
                    $_SESSION['keranjang_andgear'][$idAlatTampil] ?? 0
                );
                ?>

                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">

                        <!-- FOTO ALAT -->
                        <?php if (!empty($alat['foto'])) { ?>

                            <img
                                src="../assets/foto/<?= e($alat['foto']); ?>"
                                class="card-img-top"
                                alt="<?= e($alat['nama_alat']); ?>"
                                style="height:180px; object-fit:cover;"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                            <div
                                class="bg-body-secondary align-items-center justify-content-center"
                                style="height:180px; display:none;">
                                <span class="text-secondary small">
                                    Foto tidak ditemukan
                                </span>
                            </div>

                        <?php } else { ?>

                            <div
                                class="bg-body-secondary d-flex align-items-center justify-content-center"
                                style="height:180px;">
                                <span class="text-secondary small">
                                    Foto belum tersedia
                                </span>
                            </div>

                        <?php } ?>

                        <!-- INFORMASI ALAT -->
                        <div class="card-body d-flex flex-column">

                            <h6 class="fw-bold">
                                <?= e($alat['nama_alat']); ?>
                            </h6>

                            <p class="text-secondary small mb-3">
                                <?= e($alat['deskripsi']); ?>
                            </p>

                            <p class="text-primary fw-bold mb-1">
                                Rp <?= number_format(
                                    (float)$alat['harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                                <span class="text-secondary fw-normal small">
                                    /hari
                                </span>
                            </p>

                            <p class="small text-secondary mb-2">
                                Stok tersedia: <?= (int)$alat['stok']; ?>
                            </p>

                            <?php if ($jumlahDiKeranjang > 0) { ?>
                                <p class="small text-success mb-2">
                                    ✓ <?= $jumlahDiKeranjang; ?> unit di keranjang
                                </p>
                            <?php } ?>

                            <!-- FORM TAMBAH KERANJANG -->
                            <form method="POST" class="mt-auto">

                                <input
                                    type="hidden"
                                    name="id_alat"
                                    value="<?= $idAlatTampil; ?>"
                                >

                                <label class="form-label small mb-1">
                                    Jumlah unit
                                </label>

                                <input
                                    type="number"
                                    name="jumlah"
                                    class="form-control form-control-sm mb-2"
                                    value="1"
                                    min="1"
                                    max="<?= (int)$alat['stok']; ?>"
                                    required
                                    <?= (int)$alat['stok'] < 1 ? 'disabled' : ''; ?>
                                >

                                <?php if ((int)$alat['stok'] > 0) { ?>

                                    <button
                                        type="submit"
                                        name="tambah_keranjang"
                                        value="1"
                                        class="btn btn-primary w-100">
                                        🛒 Masukkan Keranjang
                                    </button>

                                <?php } else { ?>

                                    <button
                                        type="button"
                                        class="btn btn-secondary w-100"
                                        disabled>
                                        Stok Habis
                                    </button>

                                <?php } ?>

                            </form>

                        </div>
                    </div>
                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="col-12">
                <div class="alert alert-info">
                    Belum ada alat dalam kategori ini.
                    Silakan tambahkan data alat melalui halaman admin.
                </div>
            </div>

        <?php } ?>

    </div>

    <?php if ($totalKeranjang > 0) { ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body d-flex flex-column flex-md-row
                        justify-content-between align-items-md-center gap-3">

                <div>
                    <h5 class="fw-bold mb-1">Keranjang kamu</h5>
                    <p class="text-secondary mb-0">
                        Ada <?= (int)$totalKeranjang; ?> unit alat di keranjang.
                    </p>
                </div>

                <a href="keranjang.php" class="btn btn-success px-4">
                    Lihat Keranjang &rarr;
                </a>

            </div>
        </div>
    <?php } ?>

</main>

<!-- FOOTER -->
<footer class="bg-dark text-white text-center py-3 mt-4">
    <p class="mb-0">
        &copy; <?= date('Y'); ?> ANDGEAR - Penyewaan Alat Outdoor
    </p>
</footer>

<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
