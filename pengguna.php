<?php
	require 'config.php';
	cek_admin();
	$aksi = $_GET['aksi'] ?? 'tampil';
	$id = (int)($_GET['id'] ?? 0);
	$errors = array();
	$data = array(
		'nama' => '',
		'email' => '',
		'peran' => 'staf'
	);
	if ($aksi == 'ubah' && $_SERVER['REQUEST_METHOD'] == 'GET') {
		$stmt = $db->prepare("SELECT id, nama, email, peran FROM pengguna WHERE id = ?");
		$stmt->execute([$id]);
		$data = $stmt->fetch();
		if (!$data) {
			set_pesan('danger', 'Data pengguna tidak ditemukan.');
			header("Location: pengguna.php");
			exit;
		}
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		cek_token();
		if ($aksi == 'hapus') {
			$id = (int)$_POST['id'];
			if ($id == $_SESSION['pengguna']['id']) {
				set_pesan('danger', 'Anda tidak bisa menghapus akun yang sedang dipakai login.');
			} else {
				try {
					$stmt = $db->prepare("DELETE FROM pengguna WHERE id = ?");
					$stmt->execute([$id]);
					set_pesan('success', 'Pengguna berhasil dihapus.');
				} catch (PDOException $e) {
					set_pesan('danger', 'Pengguna tidak bisa dihapus karena sudah mencatat pesanan.');
				}
			}
			header("Location: pengguna.php");
			exit;
		}
		$id = (int)($_POST['id'] ?? 0);
		$data['nama'] = trim($_POST['nama'] ?? '');
		$data['email'] = trim($_POST['email'] ?? '');
		$data['peran'] = $_POST['peran'] ?? 'staf';
		$password = $_POST['password'] ?? '';
		if ($data['nama'] == '') {
			$errors[] = "Nama wajib diisi.";
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = "Format e-mail tidak valid.";
		}
		if ($data['peran'] != 'admin' && $data['peran'] != 'staf') {
			$errors[] = "Peran tidak valid.";
		}
		if ($aksi == 'tambah' && strlen($password) < 6) {
			$errors[] = "Password minimal 6 karakter.";
		}
		if ($aksi == 'ubah' && $password != '' && strlen($password) < 6) {
			$errors[] = "Password baru minimal 6 karakter.";
		}
		if (count($errors) == 0) {
			$cek = $db->prepare("SELECT COUNT(*) FROM pengguna WHERE email = ? AND id <> ?");
			$cek->execute([$data['email'], $id]);
			if ($cek->fetchColumn() > 0) {
				$errors[] = "E-mail sudah dipakai pengguna lain.";
			}
		}
		if (count($errors) == 0) {
			if ($aksi == 'tambah') {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $db->prepare("INSERT INTO pengguna (nama, email, password_hash, peran) VALUES (?, ?, ?, ?)");
				$stmt->execute([$data['nama'], $data['email'], $hash, $data['peran']]);
				set_pesan('success', 'Pengguna baru berhasil ditambahkan.');
			} else {
				if ($password != '') {
					$hash = password_hash($password, PASSWORD_DEFAULT);
					$stmt = $db->prepare("UPDATE pengguna SET nama = ?, email = ?, peran = ?, password_hash = ? WHERE id = ?");
					$stmt->execute([$data['nama'], $data['email'], $data['peran'], $hash, $id]);
				} else {
					$stmt = $db->prepare("UPDATE pengguna SET nama = ?, email = ?, peran = ? WHERE id = ?");
					$stmt->execute([$data['nama'], $data['email'], $data['peran'], $id]);
				}
				set_pesan('success', 'Data pengguna berhasil diubah.');
			}
			header("Location: pengguna.php");
			exit;
		}
	}
	if ($aksi == 'tampil') {
		$daftar = $db->query("SELECT id, nama, email, peran, dibuat FROM pengguna ORDER BY nama ASC")->fetchAll();
	}
	$judul_halaman = "Data Pengguna";
	include 'header.php';
?>

<?php if ($aksi == 'tambah' || $aksi == 'ubah') { ?>
	<h2 class="mb-3"><?= $aksi == 'tambah' ? 'Tambah Pengguna' : 'Ubah Pengguna' ?></h2>
	<?php if (count($errors) > 0) { ?>
		<div class="alert alert-danger">
			<ul class="mb-0">
				<?php foreach ($errors as $err) { ?>
					<li><?= e($err) ?></li>
				<?php } ?>
			</ul>
		</div>
	<?php } ?>
	<div class="card">
		<div class="card-body">
			<form method="post" action="pengguna.php?aksi=<?= e($aksi) ?>&id=<?= e($id) ?>">
				<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
				<input type="hidden" name="id" value="<?= e($id) ?>">
				<div class="row">
					<div class="col-md-6 mb-3">
						<label class="form-label">Nama</label>
						<input type="text" name="nama" class="form-control" value="<?= e($data['nama']) ?>" required>
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label">E-mail</label>
						<input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>" required>
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label">Password <?= $aksi == 'ubah' ? '(kosongkan jika tidak diganti)' : '' ?></label>
						<input type="password" name="password" class="form-control" <?= $aksi == 'tambah' ? 'required' : '' ?>>
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label">Peran</label>
						<select name="peran" class="form-select">
							<option value="staf" <?= $data['peran'] == 'staf' ? 'selected' : '' ?>>Staf Penjualan</option>
							<option value="admin" <?= $data['peran'] == 'admin' ? 'selected' : '' ?>>Admin</option>
						</select>
					</div>
				</div>
				<button type="submit" class="btn btn-primary">Simpan</button>
				<a href="pengguna.php" class="btn btn-secondary">Batal</a>
			</form>
		</div>
	</div>
<?php } else { ?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0">Data Pengguna</h2>
		<a href="pengguna.php?aksi=tambah" class="btn btn-primary">+ Tambah Pengguna</a>
	</div>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover align-middle">
					<thead>
						<tr>
							<th>No</th>
							<th>Nama</th>
							<th>E-mail</th>
							<th>Peran</th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($daftar) == 0) { ?>
							<tr>
								<td colspan="5" class="text-center text-muted py-4">Belum ada pengguna.</td>
							</tr>
						<?php } ?>
						<?php $no = 1; ?>
						<?php foreach ($daftar as $row) { ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= e($row['nama']) ?></td>
								<td><?= e($row['email']) ?></td>
								<td>
									<span class="badge <?= $row['peran'] == 'admin' ? 'bg-primary' : 'bg-secondary' ?>">
										<?= $row['peran'] == 'admin' ? 'Admin' : 'Staf Penjualan' ?>
									</span>
								</td>
								<td class="text-nowrap">
									<a href="pengguna.php?aksi=ubah&id=<?= e($row['id']) ?>" class="btn btn-sm btn-warning">Ubah</a>
									<form method="post" action="pengguna.php?aksi=hapus" class="form-hapus" data-nama="<?= e($row['nama']) ?>">
										<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
										<input type="hidden" name="id" value="<?= e($row['id']) ?>">
										<button type="submit" class="btn btn-sm btn-danger">Hapus</button>
									</form>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php } ?>
<?php include 'footer.php'; ?>