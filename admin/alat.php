<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit;
}

$error = "";
$pesan = "";

// Hapus alat
if (isset($_GET["hapus"])) {
    $id = (int) $_GET["hapus"];

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM alat WHERE id_alat = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: alat.php?pesan=hapus");
        exit;
    } else {
        $error = "Alat tidak dapat dihapus karena mungkin sudah digunakan dalam peminjaman.";
    }

    mysqli_stmt_close($stmt);
}

// Ambil data edit
$edit = null;

if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM alat WHERE id_alat = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil_edit = mysqli_stmt_get_result($stmt);
    $edit = mysqli_fetch_assoc($hasil_edit);

    mysqli_stmt_close($stmt);
}

// Tambah atau edit alat
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_alat = (int) ($_POST["id_alat"] ?? 0);
    $id_kategori = (int) ($_POST["id_kategori"] ?? 0);
    $nama_alat = trim($_POST["nama_alat"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");
    $harga = (int) ($_POST["harga"] ?? 0);
    $stok = (int) ($_POST["stok"] ?? 0);

    if (
        $id_kategori <= 0 || $nama_alat == "" ||
        $deskripsi == "" || $harga < 0 || $stok < 0
    ) {
        $error = "Semua kolom wajib diisi dengan benar.";
    } else {
        // Pastikan kategori tersedia
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori FROM kategori WHERE id_kategori = ?"
        );
        mysqli_stmt_bind_param($cek, "i", $id_kategori);
        mysqli_stmt_execute($cek);
        $kategori_ada = mysqli_stmt_get_result($cek);
        $valid_kategori = mysqli_num_rows($kategori_ada) > 0;
        mysqli_stmt_close($cek);

        if (!$valid_kategori) {
            $error = "Kategori yang dipilih tidak ditemukan.";
        } else {
            $foto = trim($_POST["foto_lama"] ?? "");

            // Upload foto jika dipilih
            if (
                isset($_FILES["foto"]) &&
                $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
                    $error = "Upload foto gagal.";
                } else {
                    $ukuran = $_FILES["foto"]["size"];
                    $ekstensi = strtolower(
                        pathinfo(
                            $_FILES["foto"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );

                    $ekstensi_diizinkan = ["jpg", "jpeg", "png", "webp"];

                    if ($ukuran > 2 * 1024 * 1024) {
                        $error = "Ukuran foto maksimal 2 MB.";
                    } elseif (!in_array($ekstensi, $ekstensi_diizinkan, true)) {
                        $error = "Foto harus berformat JPG, JPEG, PNG, atau WEBP.";
                    } else {
                        $folder_foto = "../assets/img/alat/";

                        if (!is_dir($folder_foto)) {
                            mkdir($folder_foto, 0775, true);
                        }

                        $nama_file = bin2hex(random_bytes(8)) . "." . $ekstensi;

                        if (move_uploaded_file(
                            $_FILES["foto"]["tmp_name"],
                            $folder_foto . $nama_file
                        )) {
                            $foto = $nama_file;
                        } else {
                            $error = "Foto tidak berhasil disimpan.";
                        }
                    }
                }
            }

            if ($error == "") {
                if ($id_alat > 0) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE alat
                         SET id_kategori = ?, nama_alat = ?,
                             deskripsi = ?, harga = ?, stok = ?, foto = ?
                         WHERE id_alat = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iss iisi",
                        $id_kategori,
                        $nama_alat,
                        $deskripsi,
                        $harga,
                        $stok,
                        $foto,
                        $id_alat
                    );
                } else {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO alat
                         (id_kategori, nama_alat, deskripsi, harga, stok, foto)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "issiis",
                        $id_kategori,
                        $nama_alat,
                        $deskripsi,
                        $harga,
                        $stok,
                        $foto
                    );
                }

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: alat.php?pesan=simpan");
                    exit;
                } else {
                    $error = "Data alat gagal disimpan.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

if (isset($_GET["pesan"])) {
    if ($_GET["pesan"] == "simpan") {
        $pesan = "Data alat berhasil disimpan.";
    } elseif ($_GET["pesan"] == "hapus") {
        $pesan = "Data alat berhasil dihapus.";
    }
}

$kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

$data_alat = mysqli_query(
    $koneksi,
    "SELECT a.*, k.nama_kategori
     FROM alat a
     LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
     ORDER BY a.id_alat DESC"
);

$nama_admin = $_SESSION["nama"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Alat | ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <a href="dashboard.php" class="navbar-brand fw-bold">ANDGEAR</a>
    <div class="text-white">
        <?= htmlspecialchars($nama_admin); ?>
        <a href="../logout.php" class="btn btn-outline-light btn-sm ms-2">
            Logout
        </a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">
            <h6 class="text-secondary">MENU ADMIN</h6>

            <div class="nav flex-column gap-2">
                <a href="dashboard.php" class="nav-link text-dark">Dashboard</a>
                <a href="user.php" class="nav-link text-dark">Data User</a>
                <a href="alat.php" class="nav-link active bg-primary text-white rounded">Data Alat</a>
                <a href="kategori.php" class="nav-link text-dark">Data Kategori</a>
                <a href="peminjaman.php" class="nav-link text-dark">Data Peminjaman</a>
                <a href="pengembalian.php" class="nav-link text-dark">Data Pengembalian</a>
                <a href="log_aktivitas.php" class="nav-link text-dark">Log Aktivitas</a>
            </div>
        </aside>

        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="fw-bold">Data Alat</h2>
            <p class="text-secondary">Kelola perlengkapan pendakian ANDGEAR.</p>

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

            <!-- FORM ALAT -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <?= $edit ? "Edit Alat" : "Tambah Alat"; ?>
                    </h5>
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="alat.php">
                        <input type="hidden" name="id_alat"
                               value="<?= (int) ($edit["id_alat"] ?? 0); ?>">

                        <input type="hidden" name="foto_lama"
                               value="<?= htmlspecialchars($edit["foto"] ?? ""); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Alat</label>
                                <input type="text" name="nama_alat"
                                       class="form-control" required
                                       value="<?= htmlspecialchars($edit["nama_alat"] ?? ""); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="id_kategori" class="form-select" required>
                                    <option value="">Pilih kategori</option>

                                    <?php if ($kategori): ?>
                                        <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                                            <option value="<?= (int) $k["id_kategori"]; ?>"
                                                <?= (int) ($edit["id_kategori"] ?? 0) === (int) $k["id_kategori"] ? "selected" : ""; ?>>
                                                <?= htmlspecialchars($k["nama_kategori"]); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control"
                                          rows="3" required><?= htmlspecialchars($edit["deskripsi"] ?? ""); ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Harga Sewa per Hari (Rp)</label>
                                <input type="number" name="harga" class="form-control"
                                       min="0" required
                                       value="<?= htmlspecialchars((string) ($edit["harga"] ?? "")); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok" class="form-control"
                                       min="0" required
                                       value="<?= htmlspecialchars((string) ($edit["stok"] ?? "")); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Foto Alat</label>
                                <input type="file" name="foto" class="form-control"
                                       accept=".jpg,.jpeg,.png,.webp">
                                <small class="text-secondary">Opsional, maksimal 2 MB.</small>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <?= $edit ? "Simpan Perubahan" : "Tambah Alat"; ?>
                            </button>

                            <?php if ($edit): ?>
                                <a href="alat.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL ALAT -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Daftar Peralatan</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Foto</th>
                                    <th>Nama Alat</th>
                                    <th>Kategori</th>
                                    <th>Harga/Hari</th>
                                    <th>Stok</th>
                                    <th>Deskripsi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if ($data_alat && mysqli_num_rows($data_alat) > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($a = mysqli_fetch_assoc($data_alat)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td>
                                                <?php if (!empty($a["foto"])): ?>
                                                    <img
                                                        src="../assets/img/alat/<?= rawurlencode(basename($a["foto"])); ?>"
                                                        alt="Foto alat"
                                                        style="width:70px;height:60px;object-fit:cover;"
                                                        class="rounded">
                                                <?php else: ?>
                                                    <span class="text-secondary">Belum ada foto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($a["nama_alat"]); ?></td>
                                            <td><?= htmlspecialchars($a["nama_kategori"] ?? "-"); ?></td>
                                            <td>Rp <?= number_format((int) $a["harga"], 0, ",", "."); ?></td>
                                            <td><?= (int) $a["stok"]; ?></td>
                                            <td><?= htmlspecialchars($a["deskripsi"]); ?></td>
                                            <td>
                                                <a href="alat.php?edit=<?= (int) $a["id_alat"]; ?>"
                                                   class="btn btn-warning btn-sm mb-1">
                                                    Edit
                                                </a>

                                                <a href="alat.php?hapus=<?= (int) $a["id_alat"]; ?>"
                                                   class="btn btn-danger btn-sm mb-1"
                                                   onclick="return confirm('Yakin ingin menghapus alat ini?')">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            Belum ada data alat.
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