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

// Proses ubah jumlah atau hapus alat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Hapus satu alat
    if (isset($_POST['hapus'])) {
        $idAlat = (int)$_POST['hapus'];

        unset($_SESSION['keranjang_andgear'][$idAlat]);

        $_SESSION['pesan_keranjang'] = "Alat berhasil dihapus dari keranjang.";

        header("Location: keranjang.php");
        exit;
    }

    // Kosongkan keranjang
    if (isset($_POST['kosongkan'])) {
        $_SESSION['keranjang_andgear'] = [];
        $_SESSION['pesan_keranjang'] = "Keranjang berhasil dikosongkan.";

        header("Location: keranjang.php");
        exit;
    }

    // Perbarui jumlah alat
    if (isset($_POST['perbarui'])) {
        $jumlahInput = $_POST['jumlah'] ?? [];
        $pesanError = "";

        foreach ($_SESSION['keranjang_andgear'] as $idAlat => $jumlahLama) {
            $idAlat = (int)$idAlat;
            $jumlahBaru = isset($jumlahInput[$idAlat])
                ? (int)$jumlahInput[$idAlat]
                : (int)$jumlahLama;

            if ($jumlahBaru < 1) {
                $pesanError = "Jumlah setiap alat minimal 1 unit.";
                break;
            }

            $stmtStok = mysqli_prepare(
                $koneksi,
                "SELECT stok, nama_alat FROM alat WHERE id_alat = ?"
            );

            mysqli_stmt_bind_param($stmtStok, "i", $idAlat);
            mysqli_stmt_execute($stmtStok);
            $hasilStok = mysqli_stmt_get_result($stmtStok);
            $dataStok = mysqli_fetch_assoc($hasilStok);

            if (!$dataStok) {
                unset($_SESSION['keranjang_andgear'][$idAlat]);
                continue;
            }

            if ($jumlahBaru > (int)$dataStok['stok']) {
                $pesanError = "Jumlah " . $dataStok['nama_alat']
                    . " melebihi stok yang tersedia ("
                    . (int)$dataStok['stok'] . " unit).";
                break;
            }

            $_SESSION['keranjang_andgear'][$idAlat] = $jumlahBaru;
        }

        $_SESSION['pesan_keranjang'] = $pesanError !== ""
            ? $pesanError
            : "Jumlah alat berhasil diperbarui.";

        header("Location: keranjang.php");
        exit;
    }
}

// Ambil pesan
$pesan = $_SESSION['pesan_keranjang'] ?? "";
unset($_SESSION['pesan_keranjang']);

// Ambil data alat di keranjang
$daftarAlat = [];
$totalUnit = 0;
$totalHargaPerHari = 0;

foreach ($_SESSION['keranjang_andgear'] as $idAlat => $jumlah) {
    $idAlat = (int)$idAlat;
    $jumlah = (int)$jumlah;

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_alat, nama_alat, deskripsi, harga, stok, foto
         FROM alat
         WHERE id_alat = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $idAlat);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $alat = mysqli_fetch_assoc($hasil);

    if (!$alat) {
        unset($_SESSION['keranjang_andgear'][$idAlat]);
        continue;
    }

    $alat['jumlah_keranjang'] = $jumlah;
    $daftarAlat[] = $alat;

    $totalUnit += $jumlah;
    $totalHargaPerHari += (float)$alat['harga'] * $jumlah;
}

$sudahLogin = isset($_SESSION['role'])
    && $_SESSION['role'] === 'peminjam';

$nama = $_SESSION['nama'] ?? 'Pengunjung';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Keranjang Sewa - ANDGEAR</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar bg-white border-bottom shadow-sm">
    <div class="container">

        <a href="../index.php" class="navbar-brand text-primary fw-bold">
            ANDGEAR
        </a>

        <div class="d-flex align-items-center gap-2 flex-wrap">

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

                <a href="../login.php" class="btn btn-primary btn-sm">
                    Login
                </a>

                <a href="../daftar.php" class="btn btn-outline-primary btn-sm">
                    Daftar
                </a>

            <?php } ?>

        </div>
    </div>
</nav>

<!-- ISI KERANJANG -->
<main class="container py-4">

    <a href="../index.php#kategori"
       class="btn btn-outline-primary btn-sm mb-3">
        &larr; Lanjut Pilih Alat
    </a>

    <h3 class="fw-bold mb-1">🛒 Keranjang Sewa</h3>

    <p class="text-secondary mb-4">
        Periksa perlengkapan dan jumlah unit sebelum melanjutkan penyewaan.
    </p>

    <?php if ($pesan !== "") { ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= e($pesan); ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if (count($daftarAlat) > 0) { ?>

        <form method="POST">

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Alat</th>
                                    <th>Harga/Hari</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal/Hari</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($daftarAlat as $alat) { ?>

                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-3">

                                                <?php if (!empty($alat['foto'])) { ?>

                                                    <img
                                                        src="../assets/foto/<?= e($alat['foto']); ?>"
                                                        alt="<?= e($alat['nama_alat']); ?>"
                                                        class="rounded"
                                                        style="width:75px;height:75px;object-fit:cover;"
                                                        onerror="this.style.display='none';"
                                                    >

                                                <?php } ?>

                                                <div>
                                                    <div class="fw-bold">
                                                        <?= e($alat['nama_alat']); ?>
                                                    </div>

                                                    <small class="text-secondary">
                                                        Stok: <?= (int)$alat['stok']; ?> unit
                                                    </small>
                                                </div>

                                            </div>
                                        </td>

                                        <td>
                                            Rp <?= number_format(
                                                (float)$alat['harga'],
                                                0, ',', '.'
                                            ); ?>
                                        </td>

                                        <td style="min-width:100px;">
                                            <input
                                                type="number"
                                                name="jumlah[<?= (int)$alat['id_alat']; ?>]"
                                                class="form-control form-control-sm"
                                                value="<?= (int)$alat['jumlah_keranjang']; ?>"
                                                min="1"
                                                max="<?= (int)$alat['stok']; ?>"
                                                required
                                            >
                                        </td>

                                        <td class="fw-semibold">
                                            Rp <?= number_format(
                                                (float)$alat['harga']
                                                * (int)$alat['jumlah_keranjang'],
                                                0, ',', '.'
                                            ); ?>
                                        </td>

                                        <td>
                                            <button
                                                type="submit"
                                                name="hapus"
                                                value="<?= (int)$alat['id_alat']; ?>"
                                                form="hapus-<?= (int)$alat['id_alat']; ?>"
                                                class="btn btn-outline-danger btn-sm">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="d-flex flex-column flex-md-row
                        justify-content-between gap-3 mt-3">

                <button
                    type="submit"
                    name="perbarui"
                    value="1"
                    class="btn btn-outline-primary">
                    Perbarui Jumlah
                </button>

                <div class="d-flex gap-2 flex-wrap">

                    <button
                        type="submit"
                        name="kosongkan"
                        value="1"
                        class="btn btn-outline-danger"
                        onclick="return confirm('Kosongkan semua isi keranjang?')">
                        Kosongkan Keranjang
                    </button>

                </div>
            </div>

        </form>

        <!-- FORM HAPUS TERPISAH -->
        <?php foreach ($daftarAlat as $alat) { ?>
            <form
                method="POST"
                id="hapus-<?= (int)$alat['id_alat']; ?>"
                onsubmit="return confirm('Hapus alat ini dari keranjang?')">
            </form>
        <?php } ?>

        <!-- RINGKASAN -->
        <div class="row justify-content-end mt-4">
            <div class="col-12 col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        <h5 class="fw-bold mb-3">Ringkasan Keranjang</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Jenis alat</span>
                            <span><?= count($daftarAlat); ?> jenis</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Total unit</span>
                            <span><?= (int)$totalUnit; ?> unit</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between gap-2 mb-3">
                            <span class="fw-semibold">Perkiraan biaya per hari</span>
                            <span class="fw-bold text-primary text-end">
                                Rp <?= number_format(
                                    $totalHargaPerHari,
                                    0, ',', '.'
                                ); ?>
                            </span>
                        </div>

                        <small class="text-secondary d-block mb-3">
                            Total akhir dihitung berdasarkan jumlah hari
                            penyewaan. Harga di atas belum dikalikan durasi sewa.
                        </small>

                        <?php if ($sudahLogin) { ?>

                            <a href="peminjaman.php"
                               class="btn btn-success w-100">
                                Lanjut Sewa &rarr;
                            </a>

                        <?php } else { ?>

                            <a
                                href="../login.php?redirect=<?= urlencode('peminjaman.php'); ?>"
                                class="btn btn-primary w-100">
                                Login untuk Lanjut Sewa
                            </a>

                            <p class="small text-secondary text-center mt-2 mb-0">
                                Isi keranjangmu tetap tersimpan selama sesi browser.
                            </p>

                        <?php } ?>

                    </div>
                </div>

            </div>
        </div>

    <?php } else { ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">

                <div class="display-3 mb-3">🛒</div>

                <h5 class="fw-bold">Keranjang masih kosong</h5>

                <p class="text-secondary">
                    Pilih alat outdoor yang kamu butuhkan terlebih dahulu.
                </p>

                <a href="../index.php#kategori"
                   class="btn btn-primary px-4">
                    Mulai Pilih Alat
                </a>

            </div>
        </div>

    <?php } ?>

</main>

<footer class="bg-dark text-white text-center py-3 mt-4">
    <p class="mb-0">
        &copy; <?= date('Y'); ?> ANDGEAR - Penyewaan Alat Outdoor
    </p>
</footer>

<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>