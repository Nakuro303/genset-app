<?php
	require 'config.php';
	cek_admin();
	$aksi = $_GET['aksi'] ?? 'tampil';
	$id = (int)($_GET['id'] ?? 0);
	$errors = array();
	$data = array(
		'nama_produk' => '',
		'harga' => '',
		'stok' => ''
	);
	if ($aksi == 'ubah' && $_SERVER['REQUEST_METHOD'] == 'GET') {
		$stmt = $db->prepare("SELECT * FROM produk WHERE id = ?");
		$stmt->execute([$id]);
		$data = $stmt->fetch();
		if (!$data) {
			set_pesan('danger', 'Data produk tidak ditemukan.');
			header("Location: produk.php");
			exit;
		}
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		cek_token();
		if ($aksi == 'hapus') {
			$id = (int)$_POST['id'];
			try {
				$stmt = $db->prepare("DELETE FROM produk WHERE id = ?");
				$stmt->execute([$id]);
				set_pesan('success', 'Produk berhasil dihapus.');
			} catch (PDOException $e) {
				set_pesan('danger', 'Produk tidak bisa dihapus karena sudah dipakai di data pesanan.');
			}
			header("Location: produk.php");
			exit;
		}
		$id = (int)($_POST['id'] ?? 0);
		$data['nama_produk'] = trim($_POST['nama_produk'] ?? '');
		$data['harga'] = trim($_POST['harga'] ?? '');
		$data['stok'] = trim($_POST['stok'] ?? '');
		if ($data['nama_produk'] == '') {
			$errors[] = "Nama produk wajib diisi.";
		}
		if (!ctype_digit($data['harga']) || $data['harga'] < 1) {
			$errors[] = "Harga harus berupa angka lebih dari 0.";
		}
		if (!ctype_digit($data['stok'])) {
			$errors[] = "Stok harus berupa angka 0 atau lebih.";
		}
		if (count($errors) == 0) {
			if ($aksi == 'tambah') {
				$stmt = $db->prepare("INSERT INTO produk (nama_produk, harga, stok) VALUES (?, ?, ?)");
				$stmt->execute([$data['nama_produk'], $data['harga'], $data['stok']]);
				set_pesan('success', 'Produk baru berhasil ditambahkan.');
			} else {
				$stmt = $db->prepare("UPDATE produk SET nama_produk = ?, harga = ?, stok = ? WHERE id = ?");
				$stmt->execute([$data['nama_produk'], $data['harga'], $data['stok'], $id]);
				set_pesan('success', 'Data produk berhasil diubah.');
			}
			header("Location: produk.php");
			exit;
		}
	}
	if ($aksi == 'tampil') {
		$cari = trim($_GET['cari'] ?? '');
		$kolom_boleh = array('nama_produk', 'harga', 'stok');
		$urut = in_array($_GET['urut'] ?? '', $kolom_boleh) ? $_GET['urut'] : 'nama_produk';
		$arah = (($_GET['arah'] ?? '') == 'desc') ? 'DESC' : 'ASC';
		$hal = max(1, (int)($_GET['hal'] ?? 1));
		$per_halaman = 10;
		$offset = ($hal - 1) * $per_halaman;
		$where = "";
		$param = array();
		if ($cari != '') {
			$where = "WHERE nama_produk LIKE ?";
			$param[] = "%" . $cari . "%";
		}
		$stmt = $db->prepare("SELECT COUNT(*) FROM produk $where");
		$stmt->execute($param);
		$total = $stmt->fetchColumn();
		$stmt = $db->prepare("SELECT * FROM produk $where ORDER BY $urut $arah LIMIT ? OFFSET ?");
		foreach ($param as $i => $p) {
			$stmt->bindValue($i + 1, $p);
		}
		$stmt->bindValue(count($param) + 1, $per_halaman, PDO::PARAM_INT);
		$stmt->bindValue(count($param) + 2, $offset, PDO::PARAM_INT);
		$stmt->execute();
		$daftar = $stmt->fetchAll();
	}
	$judul_halaman = "Data Produk";
	include 'header.php';
?>

<?php if ($aksi == 'tambah' || $aksi == 'ubah') { ?>
	<h2 class="mb-3"><?= $aksi == 'tambah' ? 'Tambah Produk' : 'Ubah Produk' ?></h2>
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
			<form method="post" action="produk.php?aksi=<?= e($aksi) ?>&id=<?= e($id) ?>">
				<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
				<input type="hidden" name="id" value="<?= e($id) ?>">
				<div class="row">
					<div class="col-md-6 mb-3">
						<label class="form-label">Nama Produk</label>
						<input type="text" name="nama_produk" class="form-control" value="<?= e($data['nama_produk']) ?>" required>
					</div>
					<div class="col-md-3 mb-3">
						<label class="form-label">Harga (Rp)</label>
						<input type="number" name="harga" class="form-control" min="1" value="<?= e($data['harga']) ?>" required>
					</div>
					<div class="col-md-3 mb-3">
						<label class="form-label">Stok</label>
						<input type="number" name="stok" class="form-control" min="0" value="<?= e($data['stok']) ?>" required>
					</div>
				</div>
				<button type="submit" class="btn btn-primary">Simpan</button>
				<a href="produk.php" class="btn btn-secondary">Batal</a>
			</form>
		</div>
	</div>
<?php } else { ?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0">Data Produk</h2>
		<a href="produk.php?aksi=tambah" class="btn btn-primary">+ Tambah Produk</a>
	</div>
	<form method="get" class="row g-2 mb-3">
		<div class="col-12 col-md-6">
			<input type="text" name="cari" class="form-control" placeholder="Cari nama produk" value="<?= e($cari) ?>">
		</div>
		<div class="col-12 col-md-auto">
			<button type="submit" class="btn btn-outline-primary">Cari</button>
			<a href="produk.php" class="btn btn-outline-secondary">Reset</a>
		</div>
	</form>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover align-middle">
					<thead>
						<tr>
							<th>No</th>
							<th><?= link_urut('nama_produk', 'Nama Produk', $urut, $arah) ?></th>
							<th><?= link_urut('harga', 'Harga', $urut, $arah) ?></th>
							<th><?= link_urut('stok', 'Stok', $urut, $arah) ?></th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($daftar) == 0) { ?>
							<tr>
								<td colspan="5" class="text-center text-muted py-4">Data produk masih kosong.</td>
							</tr>
						<?php } ?>
						<?php $no = $offset + 1; ?>
						<?php foreach ($daftar as $row) { ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= e($row['nama_produk']) ?></td>
								<td><?= rupiah($row['harga']) ?></td>
								<td>
									<?= e($row['stok']) ?>
									<?php if ($row['stok'] <= 5) { ?>
										<span class="badge bg-danger">Menipis</span>
									<?php } ?>
								</td>
								<td class="text-nowrap">
									<a href="produk.php?aksi=ubah&id=<?= e($row['id']) ?>" class="btn btn-sm btn-warning">Ubah</a>
									<form method="post" action="produk.php?aksi=hapus" class="form-hapus" data-nama="<?= e($row['nama_produk']) ?>">
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
			<p class="text-muted small mb-2">Total: <?= e($total) ?> produk</p>
			<?php tampil_halaman($total, $hal, $per_halaman); ?>
		</div>
	</div>
<?php } ?>
<?php include 'footer.php'; ?>