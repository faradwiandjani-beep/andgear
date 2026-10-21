<?php
session_start();
require_once "../koneksi.php";

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

// Cek login peminjam
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'peminjam'
) {
    $id_alat = (int)($_GET['id_alat'] ?? $_POST['id_alat'] ?? 0);

    $redirect = "peminjam/peminjaman.php?id_alat=" . $id_alat;

    header("Location: ../login.php?redirect=" . urlencode($redirect));
    exit;
}

$id_alat = (int)($_GET['id_alat'] ?? $_POST['id_alat'] ?? 0);

if ($id_alat <= 0) {
    header("Location: alat.php");
    exit;
}

// Ambil data alat
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_alat, nama_alat, harga, stok, foto
     FROM alat
     WHERE id_alat = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_alat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$alat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$alat) {
    header("Location: alat.php");
    exit;
}

$error = "";
$tujuan_pendakian = "";
$tanggal_pinjam = "";
$tanggal_rencana_kembali = "";
$jumlah = 1;
$catatan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tujuan_pendakian = trim($_POST['tujuan_pendakian'] ?? "");
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? "";
    $tanggal_rencana_kembali = $_POST['tanggal_rencana_kembali'] ?? "";
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? "");

    $hari_ini = date('Y-m-d');

    if ($tujuan_pendakian === "") {
        $error = "Tujuan pendakian wajib diisi.";
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_pinjam) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_rencana_kembali) ||
        !checkdate(
            (int)substr($tanggal_pinjam, 5, 2),
            (int)substr($tanggal_pinjam, 8, 2),
            (int)substr($tanggal_pinjam, 0, 4)
        ) ||
        !checkdate(
            (int)substr($tanggal_rencana_kembali, 5, 2),
            (int)substr($tanggal_rencana_kembali, 8, 2),
            (int)substr($tanggal_rencana_kembali, 0, 4)
        )
    ) {
        $error = "Tanggal peminjaman tidak valid.";
    } elseif ($tanggal_pinjam < $hari_ini) {
        $error = "Tanggal pinjam tidak boleh sebelum hari ini.";
    } elseif ($tanggal_rencana_kembali < $tanggal_pinjam) {
        $error = "Tanggal kembali harus setelah atau sama dengan tanggal pinjam.";
    } elseif ($jumlah < 1) {
        $error = "Jumlah alat minimal 1.";
    } elseif ($jumlah > (int)$alat['stok']) {
        $error = "Jumlah alat melebihi stok yang tersedia.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $id_user = (int)$_SESSION['id_user'];

            $kode_peminjaman = "PJM"
                . date("YmdHis")
                . random_int(100, 999);

            // Simpan peminjaman utama
            $sql = "INSERT INTO peminjaman
                    (
                        kode_peminjaman,
                        id_user,
                        tujuan_pendakian,
                        tanggal_pinjam,
                        tanggal_rencana_kembali,
                        status,
                        catatan
                    )
                    VALUES (?, ?, ?, ?, ?, 'menunggu', ?)";

            $stmt = mysqli_prepare($koneksi, $sql);

            if (!$stmt) {
                throw new Exception("Query peminjaman gagal disiapkan.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "sissss",
                $kode_peminjaman,
                $id_user,
                $tujuan_pendakian,
                $tanggal_pinjam,
                $tanggal_rencana_kembali,
                $catatan
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Data peminjaman gagal disimpan.");
            }

            $id_peminjaman = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt);

            // Simpan detail alat
            $sql_detail = "INSERT INTO detail_peminjaman
                           (id_peminjaman, id_alat, jumlah, catatan)
                           VALUES (?, ?, ?, ?)";

            $stmt_detail = mysqli_prepare($koneksi, $sql_detail);

            if (!$stmt_detail) {
                throw new Exception("Query detail gagal disiapkan.");
            }

            mysqli_stmt_bind_param(
                $stmt_detail,
                "iiis",
                $id_peminjaman,
                $id_alat,
                $jumlah,
                $catatan
            );

            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Detail peminjaman gagal disimpan.");
            }

            mysqli_stmt_close($stmt_detail);

            mysqli_commit($koneksi);

            // Setelah berhasil, langsung buka riwayat sewa
            header("Location: riwayat.php?sewa=berhasil");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = "Pengajuan gagal disimpan. Periksa struktur tabel database.";
        }
    }
}

$jumlah_hari = 1;

if ($tanggal_pinjam && $tanggal_rencana_kembali) {
    $selisih = (
        strtotime($tanggal_rencana_kembali) -
        strtotime($tanggal_pinjam)
    ) / 86400;

    $jumlah_hari = max(1, (int)$selisih);
}

$total_harga = (int)$alat['harga'] * $jumlah * $jumlah_hari;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Peminjaman - ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">
            ANDGEAR
        </a>

        <div class="d-flex gap-2">
            <a href="riwayat.php" class="btn btn-light btn-sm">
                Riwayat Sewa
            </a>

            <a href="../logout.php"
               class="btn btn-outline-light btn-sm"
               onclick="return confirm('Yakin ingin keluar?')">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <h3 class="fw-bold mb-1">Form Peminjaman Alat</h3>
            <p class="text-secondary mb-4">
                Isi formulir untuk mengajukan sewa alat outdoor.
            </p>

            <?php if ($error !== "") { ?>
                <div class="alert alert-danger">
                    <?= e($error); ?>
                </div>
            <?php } ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold">Alat yang Dipilih</h5>

                    <div class="d-flex align-items-center gap-3 mt-3">
                        <?php if (!empty($alat['foto'])) { ?>
                            <img
                                src="../assets/foto/<?= e($alat['foto']); ?>"
                                alt="<?= e($alat['nama_alat']); ?>"
                                class="rounded"
                                style="width:100px;height:90px;object-fit:cover;"
                                onerror="this.style.display='none';"
                            >
                        <?php } ?>

                        <div>
                            <h6 class="fw-bold mb-1">
                                <?= e($alat['nama_alat']); ?>
                            </h6>

                            <p class="mb-1">
                                Rp<?= number_format((int)$alat['harga'], 0, ',', '.'); ?>
                                / hari
                            </p>

                            <small class="text-secondary">
                                Stok tersedia: <?= (int)$alat['stok']; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <form method="post">
                        <input type="hidden"
                               name="id_alat"
                               value="<?= (int)$alat['id_alat']; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Peminjam
                            </label>

                            <input type="text"
                                   class="form-control"
                                   value="<?= e($_SESSION['nama'] ?? ''); ?>"
                                   readonly>
                        </div>

                        <div class="mb-3">
                            <label for="tujuan_pendakian"
                                   class="form-label fw-semibold">
                                Tujuan Pendakian / Kegiatan
                            </label>

                            <input type="text"
                                   name="tujuan_pendakian"
                                   id="tujuan_pendakian"
                                   class="form-control"
                                   placeholder="Contoh: Gunung Prau"
                                   value="<?= e($tujuan_pendakian); ?>"
                                   required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_pinjam"
                                       class="form-label fw-semibold">
                                    Tanggal Pinjam
                                </label>

                                <input type="date"
                                       name="tanggal_pinjam"
                                       id="tanggal_pinjam"
                                       class="form-control"
                                       min="<?= date('Y-m-d'); ?>"
                                       value="<?= e($tanggal_pinjam); ?>"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tanggal_rencana_kembali"
                                       class="form-label fw-semibold">
                                    Rencana Tanggal Kembali
                                </label>

                                <input type="date"
                                       name="tanggal_rencana_kembali"
                                       id="tanggal_rencana_kembali"
                                       class="form-control"
                                       min="<?= date('Y-m-d'); ?>"
                                       value="<?= e($tanggal_rencana_kembali); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="jumlah"
                                   class="form-label fw-semibold">
                                Jumlah Alat
                            </label>

                            <input type="number"
                                   name="jumlah"
                                   id="jumlah"
                                   class="form-control"
                                   min="1"
                                   max="<?= (int)$alat['stok']; ?>"
                                   value="<?= max(1, (int)$jumlah); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="catatan"
                                   class="form-label fw-semibold">
                                Catatan (Opsional)
                            </label>

                            <textarea name="catatan"
                                      id="catatan"
                                      class="form-control"
                                      rows="3"><?= e($catatan); ?></textarea>
                        </div>

                        <div class="alert alert-info">
                            <strong>Perkiraan biaya sewa:</strong><br>
                            Rp<?= number_format($total_harga, 0, ',', '.'); ?>

                            <div class="small mt-1">
                                Harga dihitung berdasarkan harga per hari,
                                jumlah alat, dan lama sewa.
                            </div>
                            <div class="small">
                                Status pengajuan: <strong>Menunggu</strong>.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit"
                                    class="btn btn-primary"
                                    <?= ((int)$alat['stok'] < 1) ? 'disabled' : ''; ?>
                                    onclick="return confirm('Yakin ingin mengajukan peminjaman?')">
                                Ajukan Peminjaman
                            </button>

                            <a href="alat.php"
                               class="btn btn-outline-secondary">
                                Batal
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
const tanggalPinjam = document.getElementById('tanggal_pinjam');
const tanggalKembali = document.getElementById('tanggal_rencana_kembali');

tanggalPinjam.addEventListener('change', function () {
    tanggalKembali.min = this.value;

    if (tanggalKembali.value && tanggalKembali.value < this.value) {
        tanggalKembali.value = this.value;
    }
});
</script>

</body>
</html>