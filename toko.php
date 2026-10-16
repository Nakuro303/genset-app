<?php
	require 'config.php';
	cek_login();
	$admin = ($_SESSION['pengguna']['peran'] == 'admin');
	$aksi = $_GET['aksi'] ?? 'tampil';
	$id = (int)($_GET['id'] ?? 0);
	$errors = array();
	if ($aksi != 'tampil' && !$admin) {
		set_pesan('danger', 'Staf Penjualan hanya boleh melihat data toko.');
		header("Location: toko.php");
		exit;
	}
	$data = array(
		'nama_toko' => '',
		'nama_pemilik' => '',
		'alamat' => '',
		'kota' => '',
		'no_telepon' => '',
		'email' => ''
	);
	if ($aksi == 'ubah' && $_SERVER['REQUEST_METHOD'] == 'GET') {
		$stmt = $db->prepare("SELECT * FROM toko WHERE id = ? AND aktif = 1");
		$stmt->execute([$id]);
		$data = $stmt->fetch();
		if (!$data) {
			set_pesan('danger', 'Data toko tidak ditemukan.');
			header("Location: toko.php");
			exit;
		}
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		cek_token();
		if ($aksi == 'hapus') {
			$id = (int)$_POST['id'];
			$stmt = $db->prepare("UPDATE toko SET aktif = 0 WHERE id = ?");
			$stmt->execute([$id]);
			set_pesan('success', 'Toko berhasil dihapus. Riwayat pesanannya tetap tersimpan.');
			header("Location: toko.php");
			exit;
		}
		$id = (int)($_POST['id'] ?? 0);
		$data['nama_toko'] = trim($_POST['nama_toko'] ?? '');
		$data['nama_pemilik'] = trim($_POST['nama_pemilik'] ?? '');
		$data['alamat'] = trim($_POST['alamat'] ?? '');
		$data['kota'] = trim($_POST['kota'] ?? '');
		$data['no_telepon'] = trim($_POST['no_telepon'] ?? '');
		$data['email'] = trim($_POST['email'] ?? '');
		if ($data['nama_toko'] == '') {
			$errors[] = "Nama toko wajib diisi.";
		}
		if ($data['nama_pemilik'] == '') {
			$errors[] = "Nama pemilik wajib diisi.";
		}
		if ($data['alamat'] == '') {
			$errors[] = "Alamat wajib diisi.";
		}
		if ($data['kota'] == '') {
			$errors[] = "Kota wajib diisi.";
		}
		if (!preg_match('/^[0-9+\-]{8,16}$/', $data['no_telepon'])) {
			$errors[] = "No telepon harus berupa angka (8 sampai 16 karakter).";
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = "Format e-mail tidak valid.";
		}
		if (count($errors) == 0) {
			$cek = $db->prepare("SELECT COUNT(*) FROM toko WHERE no_telepon = ? AND id <> ?");
			$cek->execute([$data['no_telepon'], $id]);
			if ($cek->fetchColumn() > 0) {
				$errors[] = "No telepon sudah dipakai toko lain.";
			}

			$cek = $db->prepare("SELECT COUNT(*) FROM toko WHERE email = ? AND id <> ?");
			$cek->execute([$data['email'], $id]);
			if ($cek->fetchColumn() > 0) {
				$errors[] = "E-mail sudah dipakai toko lain.";
			}
		}
		if (count($errors) == 0) {
			if ($aksi == 'tambah') {
				$stmt = $db->prepare("INSERT INTO toko (nama_toko, nama_pemilik, alamat, kota, no_telepon, email)
					VALUES (?, ?, ?, ?, ?, ?)");
				$stmt->execute([$data['nama_toko'], $data['nama_pemilik'], $data['alamat'],
					$data['kota'], $data['no_telepon'], $data['email']]);
				set_pesan('success', 'Toko baru berhasil ditambahkan.');
			} else {
				$stmt = $db->prepare("UPDATE toko SET nama_toko = ?, nama_pemilik = ?, alamat = ?,
					kota = ?, no_telepon = ?, email = ? WHERE id = ?");
				$stmt->execute([$data['nama_toko'], $data['nama_pemilik'], $data['alamat'],
					$data['kota'], $data['no_telepon'], $data['email'], $id]);
				set_pesan('success', 'Data toko berhasil diubah.');
			}
			header("Location: toko.php");
			exit;
		}
	}
	if ($aksi == 'tampil') {
		$cari = trim($_GET['cari'] ?? '');
		$kolom_boleh = array('nama_toko', 'nama_pemilik', 'kota', 'no_telepon', 'email');
		$urut = in_array($_GET['urut'] ?? '', $kolom_boleh) ? $_GET['urut'] : 'nama_toko';
		$arah = (($_GET['arah'] ?? '') == 'desc') ? 'DESC' : 'ASC';
		$hal = max(1, (int)($_GET['hal'] ?? 1));
		$per_halaman = 10;
		$offset = ($hal - 1) * $per_halaman;
		$where = "WHERE aktif = 1";
		$param = array();
		if ($cari != '') {
			$where .= " AND (nama_toko LIKE ? OR kota LIKE ?)";
			$param[] = "%" . $cari . "%";
			$param[] = "%" . $cari . "%";
		}
		$stmt = $db->prepare("SELECT COUNT(*) FROM toko $where");
		$stmt->execute($param);
		$total = $stmt->fetchColumn();
		$stmt = $db->prepare("SELECT * FROM toko $where ORDER BY $urut $arah LIMIT ? OFFSET ?");
		foreach ($param as $i => $p) {
			$stmt->bindValue($i + 1, $p);
		}
		$stmt->bindValue(count($param) + 1, $per_halaman, PDO::PARAM_INT);
		$stmt->bindValue(count($param) + 2, $offset, PDO::PARAM_INT);
		$stmt->execute();
		$daftar = $stmt->fetchAll();
	}
	$judul_halaman = "Data Toko";
	include 'header.php';
?>

<?php if ($aksi == 'tambah' || $aksi == 'ubah') { ?>
	<h2 class="mb-3"><?= $aksi == 'tambah' ? 'Tambah Toko' : 'Ubah Toko' ?></h2>
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
			<form method="post" action="toko.php?aksi=<?= e($aksi) ?>&id=<?= e($id) ?>">
				<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
				<input type="hidden" name="id" value="<?= e($id) ?>">
				<div class="row">
					<div class="col-md-6 mb-3">
						<label class="form-label">Nama Toko</label>
						<input type="text" name="nama_toko" class="form-control" value="<?= e($data['nama_toko']) ?>" required>
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label">Nama Pemilik</label>
						<input type="text" name="nama_pemilik" class="form-control" value="<?= e($data['nama_pemilik']) ?>" required>
					</div>
					<div class="col-12 mb-3">
						<label class="form-label">Alamat</label>
						<input type="text" name="alamat" class="form-control" value="<?= e($data['alamat']) ?>" required>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">Kota</label>
						<input type="text" name="kota" class="form-control" value="<?= e($data['kota']) ?>" required>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">No Telepon</label>
						<input type="text" name="no_telepon" class="form-control" value="<?= e($data['no_telepon']) ?>" required>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">E-mail</label>
						<input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>" required>
					</div>
				</div>
				<button type="submit" class="btn btn-primary">Simpan</button>
				<a href="toko.php" class="btn btn-secondary">Batal</a>
			</form>
		</div>
	</div>
<?php } else { ?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0">Data Toko</h2>
		<?php if ($admin) { ?>
			<a href="toko.php?aksi=tambah" class="btn btn-primary">+ Tambah Toko</a>
		<?php } ?>
	</div>
	<form method="get" class="row g-2 mb-3">
		<div class="col-12 col-md-6">
			<input type="text" name="cari" class="form-control" placeholder="Cari nama toko atau kota" value="<?= e($cari) ?>">
		</div>
		<div class="col-12 col-md-auto">
			<button type="submit" class="btn btn-outline-primary">Cari</button>
			<a href="toko.php" class="btn btn-outline-secondary">Reset</a>
		</div>
	</form>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover align-middle">
					<thead>
						<tr>
							<th>No</th>
							<th><?= link_urut('nama_toko', 'Nama Toko', $urut, $arah) ?></th>
							<th><?= link_urut('nama_pemilik', 'Pemilik', $urut, $arah) ?></th>
							<th><?= link_urut('kota', 'Kota', $urut, $arah) ?></th>
							<th><?= link_urut('no_telepon', 'No Telepon', $urut, $arah) ?></th>
							<th><?= link_urut('email', 'E-mail', $urut, $arah) ?></th>
							<?php if ($admin) { ?>
								<th>Aksi</th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php if (count($daftar) == 0) { ?>
							<tr>
								<td colspan="7" class="text-center text-muted py-4">Data toko tidak ditemukan.</td>
							</tr>
						<?php } ?>
						<?php $no = $offset + 1; ?>
						<?php foreach ($daftar as $row) { ?>
							<tr>
								<td><?= $no++ ?></td>
								<td>
									<?= e($row['nama_toko']) ?><br>
									<small class="text-muted"><?= e($row['alamat']) ?></small>
								</td>
								<td><?= e($row['nama_pemilik']) ?></td>
								<td><?= e($row['kota']) ?></td>
								<td><?= e($row['no_telepon']) ?></td>
								<td><?= e($row['email']) ?></td>
								<?php if ($admin) { ?>
									<td class="text-nowrap">
										<a href="toko.php?aksi=ubah&id=<?= e($row['id']) ?>" class="btn btn-sm btn-warning">Ubah</a>
										<form method="post" action="toko.php?aksi=hapus" class="form-hapus" data-nama="<?= e($row['nama_toko']) ?>">
											<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
											<input type="hidden" name="id" value="<?= e($row['id']) ?>">
											<button type="submit" class="btn btn-sm btn-danger">Hapus</button>
										</form>
									</td>
								<?php } ?>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<p class="text-muted small mb-2">Total: <?= e($total) ?> toko</p>
			<?php tampil_halaman($total, $hal, $per_halaman); ?>
		</div>
	</div>
<?php } ?>
<?php include 'footer.php'; ?>