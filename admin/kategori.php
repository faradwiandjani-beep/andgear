<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit;
}

$error = "";
$pesan = "";

// Hapus kategori
if (isset($_GET["hapus"])) {
    $id = (int) $_GET["hapus"];

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM kategori WHERE id_kategori = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: kategori.php?pesan=hapus");
        exit;
    } else {
        $error = "Kategori tidak bisa dihapus karena mungkin sudah digunakan oleh data alat.";
        mysqli_stmt_close($stmt);
    }
}

// Ambil kategori untuk diedit
$edit = null;

if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM kategori WHERE id_kategori = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $edit = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);
}

// Simpan kategori
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_kategori = (int) ($_POST["id_kategori"] ?? 0);
    $nama_kategori = trim($_POST["nama_kategori"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");

    if ($nama_kategori == "" || $deskripsi == "") {
        $error = "Nama kategori dan deskripsi wajib diisi!";
    } else {
        if ($id_kategori > 0) {
            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE kategori
                 SET nama_kategori = ?, deskripsi = ?
                 WHERE id_kategori = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $nama_kategori,
                $deskripsi,
                $id_kategori
            );
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO kategori (nama_kategori, deskripsi)
                 VALUES (?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $nama_kategori,
                $deskripsi
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: kategori.php?pesan=simpan");
            exit;
        } else {
            $error = "Kategori gagal disimpan!";
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET["pesan"])) {
    if ($_GET["pesan"] == "simpan") {
        $pesan = "Kategori berhasil disimpan!";
    } elseif ($_GET["pesan"] == "hapus") {
        $pesan = "Kategori berhasil dihapus!";
    }
}

// Ambil data kategori dan jumlah alat
$data_kategori = mysqli_query(
    $koneksi,
    "SELECT k.*, COUNT(a.id_alat) AS jumlah_alat
     FROM kategori k
     LEFT JOIN alat a ON k.id_kategori = a.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori, k.deskripsi
     ORDER BY k.id_kategori DESC"
);

$nama_admin = $_SESSION["nama"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori | ANDGEAR</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <a href="dashboard.php" class="navbar-brand fw-bold">ANDGEAR</a>

    <div class="text-white">
        <?= htmlspecialchars($nama_admin); ?>
        <a href="../logout.php" class="btn btn-outline-light btn-sm ms-2"
           onclick="return confirm('Yakin ingin logout?')">
            Logout
        </a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">
            <h6 class="text-secondary mb-3">MENU ADMIN</h6>

            <div class="nav flex-column gap-2">
                <a href="dashboard.php" class="nav-link text-dark">Dashboard</a>
                <a href="user.php" class="nav-link text-dark">Data User</a>
                <a href="alat.php" class="nav-link text-dark">Data Alat</a>
                <a href="kategori.php" class="nav-link active bg-primary text-white rounded">Data Kategori</a>
                <a href="peminjaman.php" class="nav-link text-dark">Data Peminjaman</a>
                <a href="pengembalian.php" class="nav-link text-dark">Data Pengembalian</a>
                <a href="log_aktivitas.php" class="nav-link text-dark">Log Aktivitas</a>
            </div>
        </aside>

        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">

            <h2 class="fw-bold">Data Kategori</h2>
            <p class="text-secondary">
                Kelola kategori perlengkapan outdoor ANDGEAR.
            </p>

            <?php if ($pesan != ""): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- FORM TAMBAH / EDIT -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <?= $edit ? "Edit Kategori" : "Tambah Kategori"; ?>
                    </h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="kategori.php">

                        <input type="hidden" name="id_kategori"
                               value="<?= (int) ($edit["id_kategori"] ?? 0); ?>">

                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori"
                                   class="form-control"
                                   placeholder="Contoh: Tenda"
                                   value="<?= htmlspecialchars($edit["nama_kategori"] ?? ""); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control"
                                      rows="3"
                                      placeholder="Masukkan deskripsi kategori"
                                      required><?= htmlspecialchars($edit["deskripsi"] ?? ""); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $edit ? "Simpan Perubahan" : "Tambah Kategori"; ?>
                        </button>

                        <?php if ($edit): ?>
                            <a href="kategori.php" class="btn btn-secondary">
                                Batal
                            </a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>

            <!-- TABEL KATEGORI -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Daftar Kategori</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah Alat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if ($data_kategori && mysqli_num_rows($data_kategori) > 0): ?>
                                    <?php $no = 1; ?>

                                    <?php while ($k = mysqli_fetch_assoc($data_kategori)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>

                                            <td class="fw-semibold">
                                                <?= htmlspecialchars($k["nama_kategori"]); ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($k["deskripsi"]); ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= (int) $k["jumlah_alat"]; ?> alat
                                                </span>
                                            </td>

                                            <td>
                                                <a href="kategori.php?edit=<?= (int) $k["id_kategori"]; ?>"
                                                   class="btn btn-warning btn-sm mb-1">
                                                    Edit
                                                </a>

                                                <a href="kategori.php?hapus=<?= (int) $k["id_kategori"]; ?>"
                                                   class="btn btn-danger btn-sm mb-1"
                                                   onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            Belum ada kategori.
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
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>