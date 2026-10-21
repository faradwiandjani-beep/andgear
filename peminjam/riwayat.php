<?php
session_start();
require_once "../koneksi.php";

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'peminjam'
) {
    header("Location: ../login.php");
    exit;
}

function e($nilai) {
    return htmlspecialchars(
        (string)$nilai,
        ENT_QUOTES,
        'UTF-8'
    );
}

$id_user = (int)$_SESSION['id_user'];

$sql = "
    SELECT
        p.id_peminjaman,
        p.kode_peminjaman,
        p.tujuan_pendakian,
        p.tanggal_pinjam,
        p.tanggal_rencana_kembali,
        p.status,
        p.catatan,
        p.created_at,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', dp.jumlah, ' unit)')
            SEPARATOR ', '
        ) AS daftar_alat
    FROM peminjaman p
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    WHERE p.id_user = ?
    GROUP BY p.id_peminjaman
    ORDER BY p.created_at DESC
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

function warnaStatus($status) {
    switch ($status) {
        case 'disetujui':
            return 'success';
        case 'ditolak':
            return 'danger';
        case 'dipinjam':
            return 'primary';
        case 'selesai':
            return 'secondary';
        default:
            return 'warning';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Sewa - ANDGEAR</title>
    <link rel="stylesheet"
          href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a href="../index.php"
           class="navbar-brand fw-bold">ANDGEAR</a>

        <div class="d-flex gap-2">
            <a href="alat.php"
               class="btn btn-outline-light btn-sm">
                Pilih Alat
            </a>

            <a href="../logout.php"
               class="btn btn-light btn-sm"
               onclick="return confirm('Yakin ingin keluar?')">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="mb-4">
        <h3 class="fw-bold">Riwayat Sewa Saya</h3>
        <p class="text-secondary">
            Halo, <?= e($_SESSION['nama'] ?? 'Peminjam'); ?>!
            Berikut daftar pengajuan peminjaman alatmu.
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <?php if ($query && mysqli_num_rows($query) > 0) { ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>No.</th>
                                <th>Kode Peminjaman</th>
                                <th>Alat</th>
                                <th>Tujuan</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php $no = 1; ?>

                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= e($row['kode_peminjaman']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($row['daftar_alat'] ?: '-'); ?>
                                </td>

                                <td>
                                    <?= e($row['tujuan_pendakian']); ?>
                                </td>

                                <td>
                                    <?= e($row['tanggal_pinjam']); ?>
                                </td>

                                <td>
                                    <?= e($row['tanggal_rencana_kembali']); ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-<?= warnaStatus($row['status']); ?>">
                                        <?= e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($row['catatan'] ?: '-'); ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

            <?php } else { ?>

                <div class="text-center py-5">
                    <h5 class="fw-bold">Belum Ada Riwayat Sewa</h5>
                    <p class="text-secondary">
                        Kamu belum pernah mengajukan peminjaman alat.
                    </p>

                    <a href="alat.php"
                       class="btn btn-primary">
                        Mulai Sewa Alat
                    </a>
                </div>

            <?php } ?>

        </div>
    </div>

    <div class="mt-3">
        <a href="../index.php"
           class="btn btn-outline-secondary">
            Kembali ke Beranda
        </a>
    </div>

</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>