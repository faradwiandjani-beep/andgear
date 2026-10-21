<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

$pesan = "";
$error = "";

// Proses tambah, edit, dan hapus pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Tambah pengguna
    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $noHp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if ($nama === '' || $username === '' || $password === '') {
            $error = "Nama, username, dan password wajib diisi.";
        } elseif (!in_array($role, ['admin', 'petugas', 'peminjam'], true)) {
            $error = "Role pengguna tidak valid.";
        } else {
            $cek = mysqli_prepare(
                $koneksi,
                "SELECT id_user FROM user WHERE username = ?"
            );
            mysqli_stmt_bind_param($cek, "s", $username);
            mysqli_stmt_execute($cek);
            $hasilCek = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($hasilCek) > 0) {
                $error = "Username sudah digunakan.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO user (nama, username, password, role, no_hp, alamat)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssss",
                    $nama,
                    $username,
                    $hash,
                    $role,
                    $noHp,
                    $alamat
                );

                if (mysqli_stmt_execute($stmt)) {
                    header("Location: user.php?status=tambah");
                    exit;
                } else {
                    $error = "Gagal menambahkan pengguna.";
                }
            }
        }
    }

    // Edit pengguna
    if ($aksi === 'edit') {
        $idUser = (int)($_POST['id_user'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $noHp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if ($idUser <= 0 || $nama === '' || $username === '') {
            $error = "Data pengguna belum lengkap.";
        } elseif (!in_array($role, ['admin', 'petugas', 'peminjam'], true)) {
            $error = "Role pengguna tidak valid.";
        } elseif ($idUser === (int)($_SESSION['id_user'] ?? 0)
            && $role !== 'admin') {
            $error = "Role akun admin yang sedang digunakan tidak dapat diubah.";
        } else {
            $cek = mysqli_prepare(
                $koneksi,
                "SELECT id_user FROM user
                 WHERE username = ? AND id_user != ?"
            );
            mysqli_stmt_bind_param($cek, "si", $username, $idUser);
            mysqli_stmt_execute($cek);
            $hasilCek = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($hasilCek) > 0) {
                $error = "Username sudah digunakan pengguna lain.";
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE user
                         SET nama = ?, username = ?, password = ?,
                             role = ?, no_hp = ?, alamat = ?
                         WHERE id_user = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssssi",
                        $nama,
                        $username,
                        $hash,
                        $role,
                        $noHp,
                        $alamat,
                        $idUser
                    );
                } else {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE user
                         SET nama = ?, username = ?, role = ?,
                             no_hp = ?, alamat = ?
                         WHERE id_user = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssi",
                        $nama,
                        $username,
                        $role,
                        $noHp,
                        $alamat,
                        $idUser
                    );
                }

                if (mysqli_stmt_execute($stmt)) {
                    header("Location: user.php?status=edit");
                    exit;
                } else {
                    $error = "Gagal memperbarui data pengguna.";
                }
            }
        }
    }

    // Hapus pengguna
    if ($aksi === 'hapus') {
        $idUser = (int)($_POST['id_user'] ?? 0);

        if ($idUser === (int)($_SESSION['id_user'] ?? 0)) {
            $error = "Kamu tidak dapat menghapus akun yang sedang digunakan.";
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "DELETE FROM user WHERE id_user = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $idUser);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: user.php?status=hapus");
                exit;
            } else {
                $error = "Pengguna tidak dapat dihapus. Mungkin data ini masih digunakan dalam transaksi.";
            }
        }
    }
}

// Pesan hasil tindakan
if (isset($_GET['status'])) {
    $status = $_GET['status'];

    if ($status === 'tambah') {
        $pesan = "Pengguna berhasil ditambahkan.";
    } elseif ($status === 'edit') {
        $pesan = "Data pengguna berhasil diperbarui.";
    } elseif ($status === 'hapus') {
        $pesan = "Pengguna berhasil dihapus.";
    }
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $kataCari = "%" . $cari . "%";

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_user, nama, username, role, no_hp, alamat, created_at
         FROM user
         WHERE nama LIKE ? OR username LIKE ?
         ORDER BY id_user DESC"
    );

    mysqli_stmt_bind_param($stmt, "ss", $kataCari, $kataCari);
} else {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_user, nama, username, role, no_hp, alamat, created_at
         FROM user
         ORDER BY id_user DESC"
    );
}

mysqli_stmt_execute($stmt);
$dataUser = mysqli_stmt_get_result($stmt);

$totalUser = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM user")
)['total'] ?? 0;

$totalAdmin = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM user WHERE role = 'admin'")
)['total'] ?? 0;

$totalPetugas = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM user WHERE role = 'petugas'")
)['total'] ?? 0;

$totalPeminjam = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM user WHERE role = 'peminjam'")
)['total'] ?? 0;

$namaAdmin = $_SESSION['nama'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data User - ANDGEAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a href="dashboard.php" class="navbar-brand fw-bold">ANDGEAR</a>

        <div class="d-flex align-items-center gap-3">
            <span class="text-white small">
                <?= e($namaAdmin); ?>
            </span>
            <a href="../logout.php"
               class="btn btn-outline-light btn-sm"
               onclick="return confirm('Yakin ingin keluar?')">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">
            <h6 class="text-secondary mb-3">MENU ADMIN</h6>

            <nav class="nav flex-column gap-2">
                <a href="dashboard.php" class="nav-link text-dark">
                    Dashboard
                </a>
                <a href="user.php" class="nav-link active bg-primary text-white rounded">
                    Data User
                </a>
                <a href="alat.php" class="nav-link text-dark">
                    Data Alat
                </a>
                <a href="kategori.php" class="nav-link text-dark">
                    Data Kategori
                </a>
                <a href="peminjaman.php" class="nav-link text-dark">
                    Data Peminjaman
                </a>
                <a href="pengembalian.php" class="nav-link text-dark">
                    Data Pengembalian
                </a>
                <a href="log_aktivitas.php" class="nav-link text-dark">
                    Log Aktivitas
                </a>
            </nav>
        </aside>

        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex flex-column flex-md-row
                        justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Data User</h3>
                    <p class="text-secondary mb-0">
                        Kelola akun admin, petugas, dan peminjam ANDGEAR.
                    </p>
                </div>

                <button class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTambah">
                    + Tambah User
                </button>
            </div>

            <?php if ($pesan !== '') { ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= e($pesan); ?>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <?php if ($error !== '') { ?>
                <div class="alert alert-danger">
                    <?= e($error); ?>
                </div>
            <?php } ?>

            <!-- STATISTIK -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-secondary mb-1">Total User</p>
                            <h3 class="fw-bold mb-0"><?= (int)$totalUser; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-secondary mb-1">Admin</p>
                            <h3 class="fw-bold text-primary mb-0"><?= (int)$totalAdmin; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-secondary mb-1">Petugas</p>
                            <h3 class="fw-bold text-success mb-0"><?= (int)$totalPetugas; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-secondary mb-1">Peminjam</p>
                            <h3 class="fw-bold text-warning mb-0"><?= (int)$totalPeminjam; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PENCARIAN -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-10">
                            <input type="text"
                                   name="cari"
                                   class="form-control"
                                   placeholder="Cari nama atau username..."
                                   value="<?= e($cari); ?>">
                        </div>

                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">
                                Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL USER -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Daftar Pengguna</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">No.</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>No. HP</th>
                                    <th>Alamat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (mysqli_num_rows($dataUser) > 0) { ?>
                                    <?php $no = 1; ?>
                                    <?php while ($user = mysqli_fetch_assoc($dataUser)) { ?>
                                        <?php
                                        $roleClass = [
                                            'admin' => 'bg-primary',
                                            'petugas' => 'bg-success',
                                            'peminjam' => 'bg-warning text-dark'
                                        ][$user['role']] ?? 'bg-secondary';
                                        ?>

                                        <tr>
                                            <td class="ps-3"><?= $no++; ?></td>
                                            <td class="fw-semibold">
                                                <?= e($user['nama']); ?>
                                            </td>
                                            <td><?= e($user['username']); ?></td>
                                            <td>
                                                <span class="badge <?= $roleClass; ?>">
                                                    <?= e(ucfirst($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td><?= e($user['no_hp'] ?: '-'); ?></td>
                                            <td><?= e($user['alamat'] ?: '-'); ?></td>

                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-warning btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEdit<?= (int)$user['id_user']; ?>">
                                                        Edit
                                                    </button>

                                                    <?php if ((int)$user['id_user'] !== (int)($_SESSION['id_user'] ?? 0)) { ?>
                                                        <form method="POST"
                                                              onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                            <input type="hidden" name="aksi" value="hapus">
                                                            <input type="hidden" name="id_user"
                                                                   value="<?= (int)$user['id_user']; ?>">
                                                            <button type="submit"
                                                                    class="btn btn-danger btn-sm">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- MODAL EDIT -->
                                        <div class="modal fade"
                                             id="modalEdit<?= (int)$user['id_user']; ?>"
                                             tabindex="-1"
                                             aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">

                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">
                                                                Edit Pengguna
                                                            </h5>
                                                            <button type="button"
                                                                    class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <input type="hidden" name="aksi" value="edit">
                                                            <input type="hidden" name="id_user"
                                                                   value="<?= (int)$user['id_user']; ?>">

                                                            <div class="mb-3">
                                                                <label class="form-label">Nama</label>
                                                                <input type="text" name="nama"
                                                                       class="form-control"
                                                                       value="<?= e($user['nama']); ?>"
                                                                       required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Username</label>
                                                                <input type="text" name="username"
                                                                       class="form-control"
                                                                       value="<?= e($user['username']); ?>"
                                                                       required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    Password baru
                                                                </label>
                                                                <input type="password" name="password"
                                                                       class="form-control"
                                                                       minlength="6"
                                                                       placeholder="Kosongkan jika tidak diganti">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Role</label>
                                                                <select name="role" class="form-select" required>
                                                                    <?php foreach (['admin', 'petugas', 'peminjam'] as $pilihanRole) { ?>
                                                                        <option value="<?= $pilihanRole; ?>"
                                                                            <?= $user['role'] === $pilihanRole ? 'selected' : ''; ?>>
                                                                            <?= ucfirst($pilihanRole); ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">No. HP</label>
                                                                <input type="text" name="no_hp"
                                                                       class="form-control"
                                                                       value="<?= e($user['no_hp']); ?>">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Alamat</label>
                                                                <textarea name="alamat"
                                                                          class="form-control"
                                                                          rows="2"><?= e($user['alamat']); ?></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button"
                                                                    class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">
                                                                Batal
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">
                                                                Simpan Perubahan
                                                            </button>
                                                        </div>
                                                    </form>

                                                </div>
                                            </div>
                                        </div>

                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-secondary">
                                            Data pengguna tidak ditemukan.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Pengguna</h5>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="aksi" value="tambah">

                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password"
                               class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="peminjam">Peminjam</option>
                            <option value="petugas">Petugas</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan Pengguna
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>