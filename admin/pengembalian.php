<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengembalian'])) {
    $id = (int) $_POST['id_pengembalian'];
    $kondisi = $_POST['kondisi_alat'] ?? '';
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    $kondisi_valid = ['baik', 'rusak ringan', 'rusak berat', 'hilang'];
    $status_valid = ['belum dikembalikan', 'diajukan', 'selesai', ''];

    if (
        in_array($kondisi, $kondisi_valid, true) &&
        in_array($status, $status_valid, true)
    ) {
        $stmt = mysqli_prepare($koneksi, "
            UPDATE pengembalian
            SET kondisi_alat = ?, status = ?, catatan = ?
            WHERE id_pengembalian = ?
        ");

        mysqli_stmt_bind_param($stmt, "sssi", $kondisi, $status, $catatan, $id);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Data pengembalian berhasil diperbarui.";
        } else {
            $pesan = "Gagal memperbarui data pengembalian.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $pesan = "Kondisi atau status pengembalian tidak valid.";
    }
}

$query = mysqli_query($koneksi, "
    SELECT pg.*,
           p.kode_peminjaman,
           u.nama AS nama_peminjam,
           a.nama_alat,
           dp.jumlah,
           pet.nama AS nama_petugas
    FROM pengembalian pg
    LEFT JOIN detail_peminjaman dp
        ON pg.id_detail = dp.id_detail
    LEFT JOIN peminjaman p
        ON dp.id_peminjaman = p.id_peminjaman
    LEFT JOIN user u
        ON p.id_user = u.id_user
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    LEFT JOIN user pet
        ON pg.id_petugas = pet.id_user
    ORDER BY pg.created_at DESC
");

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pengembalian - ANDGEAR</title>
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
                <a href="peminjaman.php" class="nav-link text-dark">Data Peminjaman</a>
                <a href="pengembalian.php" class="nav-link active bg-primary text-white rounded">Data Pengembalian</a>
                <a href="log_aktivitas.php" class="nav-link text-dark">Log Aktivitas</a>
            </div>

            <hr>
            <a href="../logout.php" class="btn btn-outline-danger w-100"
                onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        </aside>

        <main class="flex-grow-1 p-4">
            <h3 class="fw-bold mb-1">Data Pengembalian</h3>
            <p class="text-secondary mb-4">Pantau kondisi alat dan status pengembaliannya.</p>

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
                                    <th>Kode Peminjaman</th>
                                    <th>Peminjam</th>
                                    <th>Nama Alat</th>
                                    <th>Jumlah</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Kondisi Alat</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query && mysqli_num_rows($query) > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                        <tr>
                                            <form method="post">
                                                <td><?= $no++ ?></td>
                                                <td><?= e($row['kode_peminjaman']) ?></td>
                                                <td><?= e($row['nama_peminjam']) ?></td>
                                                <td><?= e($row['nama_alat']) ?></td>
                                                <td><?= (int)($row['jumlah'] ?? 0) ?></td>
                                                <td><?= e($row['tanggal_pengajuan']) ?></td>
                                                <td>
                                                    <select name="kondisi_alat" class="form-select form-select-sm" required>
                                                        <?php foreach (['baik', 'rusak ringan', 'rusak berat', 'hilang'] as $k): ?>
                                                            <option value="<?= e($k) ?>"
                                                                <?= ($row['kondisi_alat'] ?? '') === $k ? 'selected' : '' ?>>
                                                                <?= e(ucfirst($k)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="status" class="form-select form-select-sm" required>
                                                        <?php foreach (['belum dikembalikan', 'diajukan', 'selesai'] as $s): ?>
                                                            <option value="<?= e($s) ?>"
                                                                <?= ($row['status'] ?? '') === $s ? 'selected' : '' ?>>
                                                                <?= e(ucfirst($s)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="catatan"
                                                        class="form-control form-control-sm"
                                                        value="<?= e($row['catatan']) ?>"
                                                        placeholder="Catatan">
                                                </td>
                                                <td>
                                                    <input type="hidden" name="id_pengembalian"
                                                        value="<?= (int)$row['id_pengembalian'] ?>">
                                                    <button type="submit" name="simpan_pengembalian"
                                                        class="btn btn-primary btn-sm">Simpan</button>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-secondary py-4">
                                            Belum ada data pengembalian.
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