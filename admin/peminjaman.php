<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $id = (int) $_POST['id_peminjaman'];
    $status = $_POST['status'];

    $status_valid = ['menunggu', 'disetujui', 'ditolak', 'dipinjam', 'selesai'];

    if (in_array($status, $status_valid, true)) {
        if ($status === 'disetujui') {
            $stmt = mysqli_prepare($koneksi, "
                UPDATE peminjaman
                SET status = ?, tanggal_disetujui = CURDATE()
                WHERE id_peminjaman = ?
            ");
        } else {
            $stmt = mysqli_prepare($koneksi, "
                UPDATE peminjaman SET status = ?
                WHERE id_peminjaman = ?
            ");
        }

        mysqli_stmt_bind_param($stmt, "si", $status, $id);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Status peminjaman berhasil diperbarui.";
        } else {
            $pesan = "Gagal memperbarui status peminjaman.";
        }

        mysqli_stmt_close($stmt);
    }
}

$query = mysqli_query($koneksi, "
    SELECT p.*, u.nama AS nama_peminjam,
           COUNT(dp.id_detail) AS jumlah_item
    FROM peminjaman p
    LEFT JOIN user u ON p.id_user = u.id_user
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    GROUP BY p.id_peminjaman
    ORDER BY p.created_at DESC
");

function e($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Peminjaman - ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="d-flex min-vh-100">
    <aside class="bg-white border-end p-3" style="width:260px; flex-shrink:0;">
        <h4 class="text-primary fw-bold mb-4">ANDGEAR</h4>

        <div class="nav flex-column gap-2">
            <a href="dashboard.php" class="nav-link text-dark">Dashboard</a>
            <a href="user.php" class="nav-link text-dark">Data User</a>
            <a href="alat.php" class="nav-link text-dark">Data Alat</a>
            <a href="kategori.php" class="nav-link text-dark">Data Kategori</a>
            <a href="peminjaman.php" class="nav-link active bg-primary text-white rounded">Data Peminjaman</a>
            <a href="pengembalian.php" class="nav-link text-dark">Data Pengembalian</a>
            <a href="log_aktivitas.php" class="nav-link text-dark">Log Aktivitas</a>
        </div>

        <hr>
        <a href="../logout.php" class="btn btn-outline-danger w-100"
           onclick="return confirm('Yakin ingin keluar?')">Logout</a>
    </aside>

    <main class="flex-grow-1 p-4">
        <h3 class="fw-bold mb-1">Data Peminjaman</h3>
        <p class="text-secondary mb-4">Kelola permintaan peminjaman alat pendakian.</p>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-info"><?= e($pesan) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>No.</th>
                                <th>Kode</th>
                                <th>Peminjam</th>
                                <th>Tujuan Pendakian</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Jumlah Item</th>
                                <th>Status</th>
                                <th>Ubah Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0): ?>
                            <?php $no = 1; ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($row['kode_peminjaman']) ?></td>
                                    <td><?= e($row['nama_peminjam']) ?></td>
                                    <td><?= e($row['tujuan_pendakian']) ?></td>
                                    <td><?= e($row['tanggal_pinjam']) ?></td>
                                    <td><?= e($row['tanggal_rencana_kembali']) ?></td>
                                    <td><?= (int)$row['jumlah_item'] ?></td>
                                    <td>
                                        <span class="badge text-bg-<?=
                                            $row['status'] === 'disetujui' ? 'success' :
                                            ($row['status'] === 'ditolak' ? 'danger' :
                                            ($row['status'] === 'selesai' ? 'secondary' : 'warning'))
                                        ?>">
                                            <?= e(ucfirst($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="id_peminjaman"
                                                   value="<?= (int)$row['id_peminjaman'] ?>">
                                            <select name="status" class="form-select form-select-sm" required>
                                                <?php foreach (['menunggu','disetujui','ditolak','dipinjam','selesai'] as $s): ?>
                                                    <option value="<?= e($s) ?>"
                                                        <?= $row['status'] === $s ? 'selected' : '' ?>>
                                                        <?= e(ucfirst($s)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="ubah_status"
                                                    class="btn btn-primary btn-sm">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-4">
                                    Belum ada data peminjaman.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>