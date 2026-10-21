<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

$pesan = "";
$jenisPesan = "info";

$kondisiValid = ['baik', 'rusak ringan', 'rusak berat', 'hilang'];
$statusValid = ['belum dikembalikan', 'diajukan', 'selesai'];

/* SIMPAN DATA PENGEMBALIAN */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengembalian'])) {
    $idDetail = (int)($_POST['id_detail'] ?? 0);
    $kondisi = $_POST['kondisi_alat'] ?? 'baik';
    $status = $_POST['status'] ?? 'belum dikembalikan';
    $catatan = trim($_POST['catatan'] ?? '');

    if (
        $idDetail <= 0 ||
        !in_array($kondisi, $kondisiValid, true) ||
        !in_array($status, $statusValid, true)
    ) {
        $pesan = "Data yang dimasukkan tidak valid.";
        $jenisPesan = "danger";
    } else {
        /* Pastikan peminjaman sudah disetujui atau sedang dipinjam */
        $cek = mysqli_prepare($koneksi, "
            SELECT dp.id_detail
            FROM detail_peminjaman dp
            JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
            WHERE dp.id_detail = ?
              AND p.status IN ('disetujui', 'dipinjam')
            LIMIT 1
        ");

        mysqli_stmt_bind_param($cek, "i", $idDetail);
        mysqli_stmt_execute($cek);
        $detail = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        if (!$detail) {
            $pesan = "Peminjaman tidak ditemukan atau belum disetujui.";
            $jenisPesan = "danger";
        } else {
            /* Cari data pengembalian berdasarkan detail alat */
            $cekPg = mysqli_prepare($koneksi, "
                SELECT id_pengembalian
                FROM pengembalian
                WHERE id_detail = ?
                LIMIT 1
            ");

            mysqli_stmt_bind_param($cekPg, "i", $idDetail);
            mysqli_stmt_execute($cekPg);
            $dataPg = mysqli_fetch_assoc(mysqli_stmt_get_result($cekPg));
            mysqli_stmt_close($cekPg);

            $idPetugas = (int)($_SESSION['id_user'] ?? 0);
            $waktuSekarang = date('Y-m-d H:i:s');

            /*
             * tanggal_kembali diisi waktu sekarang jika status selesai.
             * Untuk status lainnya, gunakan waktu sekarang sementara
             * karena kolom database mewajibkan nilai non-NULL.
             */
            $tanggalKembali = $waktuSekarang;

            if ($dataPg) {
                $idPg = (int)$dataPg['id_pengembalian'];

                if ($status === 'selesai') {
                    $simpan = mysqli_prepare($koneksi, "
                        UPDATE pengembalian
                        SET kondisi_alat = ?,
                            status = ?,
                            catatan = ?,
                            tanggal_kembali = ?
                        WHERE id_pengembalian = ?
                    ");

                    if ($simpan) {
                        mysqli_stmt_bind_param(
                            $simpan,
                            "ssssi",
                            $kondisi,
                            $status,
                            $catatan,
                            $tanggalKembali,
                            $idPg
                        );
                    }
                } else {
                    $simpan = mysqli_prepare($koneksi, "
                        UPDATE pengembalian
                        SET kondisi_alat = ?,
                            status = ?,
                            catatan = ?
                        WHERE id_pengembalian = ?
                    ");

                    if ($simpan) {
                        mysqli_stmt_bind_param(
                            $simpan,
                            "sssi",
                            $kondisi,
                            $status,
                            $catatan,
                            $idPg
                        );
                    }
                }
            } else {
                $tanggalPengajuan = $waktuSekarang;

                $simpan = mysqli_prepare($koneksi, "
                    INSERT INTO pengembalian
                    (
                        id_detail,
                        id_petugas,
                        tanggal_pengajuan,
                        tanggal_kembali,
                        kondisi_alat,
                        status,
                        catatan
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if ($simpan) {
                    mysqli_stmt_bind_param(
                        $simpan,
                        "iisssss",
                        $idDetail,
                        $idPetugas,
                        $tanggalPengajuan,
                        $tanggalKembali,
                        $kondisi,
                        $status,
                        $catatan
                    );
                }
            }

            if (isset($simpan) && $simpan instanceof mysqli_stmt) {
                if (mysqli_stmt_execute($simpan)) {
                    $pesan = "Data pengembalian berhasil disimpan.";
                    $jenisPesan = "success";

                    /* Jika selesai, ubah status peminjaman */
                    if ($status === 'selesai') {
                        $ubah = mysqli_prepare($koneksi, "
                            UPDATE peminjaman p
                            JOIN detail_peminjaman dp
                                ON dp.id_peminjaman = p.id_peminjaman
                            SET p.status = 'selesai'
                            WHERE dp.id_detail = ?
                              AND NOT EXISTS (
                                  SELECT 1
                                  FROM detail_peminjaman dp2
                                  JOIN pengembalian pg2
                                      ON pg2.id_detail = dp2.id_detail
                                  WHERE dp2.id_peminjaman = p.id_peminjaman
                                    AND pg2.status <> 'selesai'
                              )
                        ");

                        if ($ubah) {
                            mysqli_stmt_bind_param($ubah, "i", $idDetail);
                            mysqli_stmt_execute($ubah);
                            mysqli_stmt_close($ubah);
                        }
                    }
                } else {
                    $pesan = "Gagal menyimpan data: " . mysqli_stmt_error($simpan);
                    $jenisPesan = "danger";
                }

                mysqli_stmt_close($simpan);
            } else {
                $pesan = "Gagal menyiapkan query: " . mysqli_error($koneksi);
                $jenisPesan = "danger";
            }
        }
    }
}

/* TAMPILKAN PEMINJAMAN YANG SUDAH DISETUJUI ATAU SEDANG DIPINJAM */
$query = mysqli_query($koneksi, "
    SELECT
        dp.id_detail,
        dp.jumlah,
        p.kode_peminjaman,
        p.status AS status_peminjaman,
        u.nama AS nama_peminjam,
        a.nama_alat,
        pg.id_pengembalian,
        pg.kondisi_alat,
        pg.status AS status_pengembalian,
        pg.catatan
    FROM detail_peminjaman dp
    JOIN peminjaman p
        ON p.id_peminjaman = dp.id_peminjaman
    JOIN user u
        ON u.id_user = p.id_user
    JOIN alat a
        ON a.id_alat = dp.id_alat
    LEFT JOIN pengembalian pg
        ON pg.id_detail = dp.id_detail
    WHERE p.status IN ('disetujui', 'dipinjam')
    ORDER BY p.created_at DESC, dp.id_detail DESC
");

if (!$query && $pesan === "") {
    $pesan = "Query gagal: " . mysqli_error($koneksi);
    $jenisPesan = "danger";
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
    <aside class="bg-white border-end p-3" style="width:260px;flex-shrink:0;">
        <h4 class="text-primary fw-bold mb-1">ANDGEAR</h4>
        <p class="text-secondary small mb-4">Panel Petugas</p>

        <div class="nav flex-column gap-2">
            <a href="dashboard.php" class="nav-link text-dark">Dashboard</a>
            <a href="peminjaman.php" class="nav-link text-dark">Data Peminjaman</a>
            <a href="pengembalian.php" class="nav-link active bg-primary text-white rounded">
                Data Pengembalian
            </a>
        </div>

        <hr>
        <a href="../logout.php" class="btn btn-outline-danger w-100"
           onclick="return confirm('Yakin ingin keluar?')">
            Logout
        </a>
    </aside>

    <main class="flex-grow-1 p-4">
        <h3 class="fw-bold mb-1">Data Pengembalian</h3>
        <p class="text-secondary mb-4">
            Kelola kondisi dan proses pengembalian alat.
        </p>

        <?php if ($pesan !== ""): ?>
            <div class="alert alert-<?= e($jenisPesan) ?>">
                <?= e($pesan) ?>
            </div>
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
                                <th>Status Sewa</th>
                                <th>Kondisi Alat</th>
                                <th>Status Pengembalian</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0): ?>
                            <?php $no = 1; ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                <?php
                                $kondisiTerpilih = $row['kondisi_alat'] ?: 'baik';
                                $statusTerpilih = $row['status_pengembalian'] ?: 'belum dikembalikan';
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= e($row['kode_peminjaman']); ?></td>
                                    <td><?= e($row['nama_peminjam']); ?></td>
                                    <td><?= e($row['nama_alat']); ?></td>
                                    <td><?= (int)$row['jumlah']; ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= e(ucfirst($row['status_peminjaman'])); ?>
                                        </span>
                                    </td>
                                    <td colspan="4">
                                        <form method="post" class="row g-2 align-items-center">
                                            <input type="hidden" name="id_detail"
                                                   value="<?= (int)$row['id_detail']; ?>">

                                            <div class="col-lg-3">
                                                <select name="kondisi_alat" class="form-select form-select-sm" required>
                                                    <?php foreach ($kondisiValid as $k): ?>
                                                        <option value="<?= e($k); ?>"
                                                            <?= $kondisiTerpilih === $k ? 'selected' : ''; ?>>
                                                            <?= e(ucfirst($k)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-lg-3">
                                                <select name="status" class="form-select form-select-sm" required>
                                                    <?php foreach ($statusValid as $s): ?>
                                                        <option value="<?= e($s); ?>"
                                                            <?= $statusTerpilih === $s ? 'selected' : ''; ?>>
                                                            <?= e(ucfirst($s)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-lg-4">
                                                <input type="text" name="catatan"
                                                       class="form-control form-control-sm"
                                                       value="<?= e($row['catatan'] ?? ''); ?>"
                                                       placeholder="Catatan">
                                            </div>

                                            <div class="col-lg-2">
                                                <button type="submit" name="simpan_pengembalian"
                                                        value="1" class="btn btn-primary btn-sm w-100">
                                                    Simpan
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-secondary py-4">
                                    Belum ada peminjaman berstatus disetujui atau dipinjam.
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