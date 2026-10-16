<?php
	require 'config.php';
	cek_login();
	$admin = ($_SESSION['pengguna']['peran'] == 'admin');
	$aksi = $_GET['aksi'] ?? 'tampil';
	$id = (int)($_GET['id'] ?? 0);
	$errors = array();
	$daftar_status = array('baru', 'diproses', 'selesai', 'batal');
	$warna_status = array('baru' => 'info', 'diproses' => 'warning', 'selesai' => 'success', 'batal' => 'secondary');
	if ($aksi == 'hapus' && !$admin) {
		set_pesan('danger', 'Staf Penjualan tidak boleh menghapus pesanan.');
		header("Location: pesanan.php");
		exit;
	}
	$data = array(
		'toko_id' => '',
		'tanggal' => date('Y-m-d'),
		'status' => 'baru'
	);
	$baris_item = array(array('produk_id' => '', 'jumlah' => 1));
	$pesanan = null;
	$item_pesanan = array();
	if ($aksi == 'ubah' || $aksi == 'detail') {
		$stmt = $db->prepare("SELECT p.*, t.nama_toko, t.kota, u.nama AS nama_staf
			FROM pesanan p
			JOIN toko t ON t.id = p.toko_id
			JOIN pengguna u ON u.id = p.pengguna_id
			WHERE p.id = ?");
		$stmt->execute([$id]);
		$pesanan = $stmt->fetch();
		if (!$pesanan) {
			set_pesan('danger', 'Data pesanan tidak ditemukan.');
			header("Location: pesanan.php");
			exit;
		}
		$stmt = $db->prepare("SELECT d.*, pr.nama_produk
			FROM detail_pesanan d
			JOIN produk pr ON pr.id = d.produk_id
			WHERE d.pesanan_id = ?");
		$stmt->execute([$id]);
		$item_pesanan = $stmt->fetchAll();
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$data['toko_id'] = $pesanan['toko_id'];
			$data['tanggal'] = $pesanan['tanggal'];
			$data['status'] = $pesanan['status'];
		}
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		cek_token();
		if ($aksi == 'hapus') {
			$id = (int)$_POST['id'];
			try {
				$db->beginTransaction();
				$cek = $db->prepare("SELECT status FROM pesanan WHERE id = ? FOR UPDATE");
				$cek->execute([$id]);
				$status_lama = $cek->fetchColumn();
				if ($status_lama && $status_lama != 'batal') {
					$stmt = $db->prepare("UPDATE produk pr
						JOIN detail_pesanan d ON d.produk_id = pr.id
						SET pr.stok = pr.stok + d.jumlah
						WHERE d.pesanan_id = ?");
					$stmt->execute([$id]);
				}
				$stmt = $db->prepare("DELETE FROM pesanan WHERE id = ?");
				$stmt->execute([$id]);
				$db->commit();
				set_pesan('success', 'Pesanan berhasil dihapus dan stok dikembalikan.');
			} catch (PDOException $e) {
				if ($db->inTransaction()) {
					$db->rollBack();
				}
				set_pesan('danger', 'Pesanan gagal dihapus karena terjadi kesalahan database.');
			}
			header("Location: pesanan.php");
			exit;
		}
		$data['toko_id'] = (int)($_POST['toko_id'] ?? 0);
		$data['tanggal'] = trim($_POST['tanggal'] ?? '');
		$toko_lama = ($aksi == 'ubah') ? $pesanan['toko_id'] : 0;
		$cek = $db->prepare("SELECT COUNT(*) FROM toko WHERE id = ? AND (aktif = 1 OR id = ?)");
		$cek->execute([$data['toko_id'], $toko_lama]);
		if ($cek->fetchColumn() == 0) {
			$errors[] = "Toko tidak valid, silakan pilih toko dari daftar.";
		}
		$tgl = DateTime::createFromFormat('Y-m-d', $data['tanggal']);
		if (!$tgl || $tgl->format('Y-m-d') != $data['tanggal']) {
			$errors[] = "Format tanggal tidak valid.";
		}
		if ($aksi == 'tambah') {
			$produk_ids = $_POST['produk_id'] ?? array();
			$jumlahs = $_POST['jumlah'] ?? array();
			if (!is_array($produk_ids) || !is_array($jumlahs)) {
				$produk_ids = array();
				$jumlahs = array();
			}
			$item = array();
			$baris_item = array();
			for ($i = 0; $i < count($produk_ids); $i++) {
				$pid = (int)$produk_ids[$i];
				$jml = $jumlahs[$i] ?? '';
				$baris_item[] = array('produk_id' => $pid, 'jumlah' => $jml);
				if ($pid < 1) {
					$errors[] = "Produk pada baris " . ($i + 1) . " belum dipilih.";
					continue;
				}
				if (!ctype_digit((string)$jml) || $jml < 1) {
					$errors[] = "Jumlah pada baris " . ($i + 1) . " harus berupa angka lebih dari 0.";
					continue;
				}
				$item[$pid] = ($item[$pid] ?? 0) + (int)$jml;
			}
			if (count($baris_item) == 0) {
				$baris_item = array(array('produk_id' => '', 'jumlah' => 1));
				$errors[] = "Pilih minimal satu produk.";
			}
			if (count($errors) == 0) {
				ksort($item);
				try {
					$db->beginTransaction();
					$stmt = $db->prepare("INSERT INTO pesanan (toko_id, pengguna_id, tanggal, status, total)
						VALUES (?, ?, ?, 'baru', 0)");
					$stmt->execute([$data['toko_id'], $_SESSION['pengguna']['id'], $data['tanggal']]);
					$pesanan_id = $db->lastInsertId();
					$total = 0;
					foreach ($item as $pid => $jml) {
						$stmt = $db->prepare("SELECT nama_produk, harga, stok FROM produk WHERE id = ? FOR UPDATE");
						$stmt->execute([$pid]);
						$produk = $stmt->fetch();
						if (!$produk) {
							throw new Exception("Produk tidak ditemukan.");
						}
						if ($jml > $produk['stok']) {
							throw new Exception("Stok " . $produk['nama_produk'] . " tidak cukup. Tersedia " . $produk['stok'] . " unit.");
						}
						$subtotal = $produk['harga'] * $jml;
						$total += $subtotal;
						$stmt = $db->prepare("INSERT INTO detail_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal)
							VALUES (?, ?, ?, ?, ?)");
						$stmt->execute([$pesanan_id, $pid, $jml, $produk['harga'], $subtotal]);
						$stmt = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
						$stmt->execute([$jml, $pid]);
					}
					$stmt = $db->prepare("UPDATE pesanan SET total = ? WHERE id = ?");
					$stmt->execute([$total, $pesanan_id]);
					$db->commit();
					set_pesan('success', 'Pesanan berhasil dibuat. Total dihitung otomatis: ' . rupiah($total));
					header("Location: pesanan.php?aksi=detail&id=" . $pesanan_id);
					exit;
				} catch (PDOException $e) {
					if ($db->inTransaction()) {
						$db->rollBack();
					}
					$errors[] = "Terjadi kesalahan database, pesanan tidak disimpan.";
				} catch (Exception $e) {
					if ($db->inTransaction()) {
						$db->rollBack();
					}
					$errors[] = $e->getMessage();
				}
			}
		}
		if ($aksi == 'ubah') {
			$data['status'] = $_POST['status'] ?? '';
			if (!in_array($data['status'], $daftar_status)) {
				$errors[] = "Status tidak valid.";
			}

			if (count($errors) == 0) {
				try {
					$db->beginTransaction();

					$cek = $db->prepare("SELECT status FROM pesanan WHERE id = ? FOR UPDATE");
					$cek->execute([$id]);
					$status_lama = $cek->fetchColumn();

					if ($status_lama == 'batal') {
						throw new Exception("Pesanan yang sudah dibatalkan tidak bisa diubah lagi.");
					}

					if ($data['status'] == 'batal') {
						$stmt = $db->prepare("UPDATE produk pr
							JOIN detail_pesanan d ON d.produk_id = pr.id
							SET pr.stok = pr.stok + d.jumlah
							WHERE d.pesanan_id = ?");
						$stmt->execute([$id]);
					}

					$stmt = $db->prepare("UPDATE pesanan SET toko_id = ?, tanggal = ?, status = ? WHERE id = ?");
					$stmt->execute([$data['toko_id'], $data['tanggal'], $data['status'], $id]);

					$db->commit();
					set_pesan('success', 'Pesanan berhasil diubah.');
					header("Location: pesanan.php");
					exit;
				} catch (PDOException $e) {
					if ($db->inTransaction()) {
						$db->rollBack();
					}
					$errors[] = "Terjadi kesalahan database, perubahan tidak disimpan.";
				} catch (Exception $e) {
					if ($db->inTransaction()) {
						$db->rollBack();
					}
					$errors[] = $e->getMessage();
				}
			}
		}
	}
	if ($aksi == 'tambah' || $aksi == 'ubah') {
		$stmt = $db->prepare("SELECT id, nama_toko, kota FROM toko WHERE aktif = 1 OR id = ? ORDER BY nama_toko");
		$stmt->execute([$pesanan['toko_id'] ?? 0]);
		$daftar_toko = $stmt->fetchAll();
	}
	if ($aksi == 'tambah') {
		$daftar_produk = $db->query("SELECT id, nama_produk, harga, stok FROM produk ORDER BY nama_produk")->fetchAll();
	}
	if ($aksi == 'tampil') {
		$cari = trim($_GET['cari'] ?? '');
		$status_filter = $_GET['status'] ?? '';
		$dari = $_GET['dari'] ?? '';
		$sampai = $_GET['sampai'] ?? '';

		$kolom_map = array(
			'id' => 'p.id',
			'tanggal' => 'p.tanggal',
			'nama_toko' => 't.nama_toko',
			'status' => 'p.status',
			'total' => 'p.total'
		);
		$urut = array_key_exists($_GET['urut'] ?? '', $kolom_map) ? $_GET['urut'] : 'tanggal';
		$arah = (($_GET['arah'] ?? 'desc') == 'asc') ? 'ASC' : 'DESC';
		$hal = max(1, (int)($_GET['hal'] ?? 1));
		$per_halaman = 10;
		$offset = ($hal - 1) * $per_halaman;
		$where = "WHERE 1 = 1";
		$param = array();
		if ($cari != '') {
			$where .= " AND (t.nama_toko LIKE ? OR t.kota LIKE ?)";
			$param[] = "%" . $cari . "%";
			$param[] = "%" . $cari . "%";
		}
		if (in_array($status_filter, $daftar_status)) {
			$where .= " AND p.status = ?";
			$param[] = $status_filter;
		}
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
			$where .= " AND p.tanggal >= ?";
			$param[] = $dari;
		}
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
			$where .= " AND p.tanggal <= ?";
			$param[] = $sampai;
		}
		$stmt = $db->prepare("SELECT COUNT(*) FROM pesanan p JOIN toko t ON t.id = p.toko_id $where");
		$stmt->execute($param);
		$total = $stmt->fetchColumn();
		$stmt = $db->prepare("SELECT p.id, p.tanggal, p.status, p.total, t.nama_toko, t.kota, u.nama AS nama_staf
			FROM pesanan p
			JOIN toko t ON t.id = p.toko_id
			JOIN pengguna u ON u.id = p.pengguna_id
			$where
			ORDER BY " . $kolom_map[$urut] . " $arah, p.id DESC
			LIMIT ? OFFSET ?");
		foreach ($param as $i => $p) {
			$stmt->bindValue($i + 1, $p);
		}
		$stmt->bindValue(count($param) + 1, $per_halaman, PDO::PARAM_INT);
		$stmt->bindValue(count($param) + 2, $offset, PDO::PARAM_INT);
		$stmt->execute();
		$daftar = $stmt->fetchAll();
	}
	$judul_halaman = "Data Pesanan";
	include 'header.php';
?>

<?php if ($aksi == 'tambah' || $aksi == 'ubah') { ?>
	<h2 class="mb-3"><?= $aksi == 'tambah' ? 'Buat Pesanan Baru' : 'Ubah Pesanan #' . e($id) ?></h2>
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
			<form method="post" id="form-pesanan" action="pesanan.php?aksi=<?= e($aksi) ?>&id=<?= e($id) ?>">
				<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
				<div class="row">
					<div class="col-md-5 mb-3">
						<label class="form-label">Toko</label>
						<select name="toko_id" class="form-select" required>
							<option value="">-- Pilih Toko --</option>
							<?php foreach ($daftar_toko as $t) { ?>
								<option value="<?= e($t['id']) ?>" <?= $data['toko_id'] == $t['id'] ? 'selected' : '' ?>>
									<?= e($t['nama_toko']) ?> - <?= e($t['kota']) ?>
								</option>
							<?php } ?>
						</select>
					</div>
					<div class="col-md-3 mb-3">
						<label class="form-label">Tanggal</label>
						<input type="date" name="tanggal" class="form-control" value="<?= e($data['tanggal']) ?>" required>
					</div>
					<?php if ($aksi == 'ubah') { ?>
						<div class="col-md-4 mb-3">
							<label class="form-label">Status</label>
							<select name="status" class="form-select">
								<?php foreach ($daftar_status as $s) { ?>
									<option value="<?= e($s) ?>" <?= $data['status'] == $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
								<?php } ?>
							</select>
						</div>
					<?php } ?>
				</div>
				<?php if ($aksi == 'tambah') { ?>
					<h5 class="mt-2">Produk yang Dipesan</h5>
					<div id="daftar-item">
						<?php foreach ($baris_item as $b) { ?>
							<div class="row baris-item">
								<div class="col-7">
									<select name="produk_id[]" class="form-select">
										<option value="" data-stok="0">-- Pilih Produk --</option>
										<?php foreach ($daftar_produk as $pr) { ?>
											<option value="<?= e($pr['id']) ?>" data-stok="<?= e($pr['stok']) ?>" <?= $b['produk_id'] == $pr['id'] ? 'selected' : '' ?>>
												<?= e($pr['nama_produk']) ?> (<?= rupiah($pr['harga']) ?>, stok <?= e($pr['stok']) ?>)
											</option>
										<?php } ?>
									</select>
								</div>
								<div class="col-3">
									<input type="number" name="jumlah[]" class="form-control" value="<?= e($b['jumlah']) ?>" min="1">
								</div>
								<div class="col-2">
									<button type="button" class="btn btn-outline-danger hapus-baris">X</button>
								</div>
							</div>
						<?php } ?>
					</div>
					<button type="button" id="tambah-baris" class="btn btn-outline-secondary btn-sm mb-3">+ Tambah Produk</button>
					<p class="text-muted small">Total harga dihitung otomatis oleh sistem saat pesanan disimpan.</p>
				<?php } else { ?>
					<h5 class="mt-2">Isi Pesanan</h5>
					<div class="table-responsive">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Produk</th>
									<th>Jumlah</th>
									<th>Subtotal</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($item_pesanan as $it) { ?>
									<tr>
										<td><?= e($it['nama_produk']) ?></td>
										<td><?= e($it['jumlah']) ?></td>
										<td><?= rupiah($it['subtotal']) ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<p class="text-muted small">Produk dan jumlah tidak bisa diubah. Jika salah, batalkan pesanan ini lalu buat pesanan baru.</p>
				<?php } ?>
				<button type="submit" class="btn btn-primary">Simpan</button>
				<a href="pesanan.php" class="btn btn-secondary">Batal</a>
			</form>
		</div>
	</div>
<?php } elseif ($aksi == 'detail') { ?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0">Detail Pesanan #<?= e($pesanan['id']) ?></h2>
		<a href="pesanan.php" class="btn btn-secondary">Kembali</a>
	</div>
	<div class="card mb-3">
		<div class="card-body">
			<div class="row">
				<div class="col-md-6">
					<p class="mb-1"><b>Toko:</b> <?= e($pesanan['nama_toko']) ?> (<?= e($pesanan['kota']) ?>)</p>
					<p class="mb-1"><b>Tanggal:</b> <?= e(date('d-m-Y', strtotime($pesanan['tanggal']))) ?></p>
				</div>
				<div class="col-md-6">
					<p class="mb-1"><b>Dibuat oleh:</b> <?= e($pesanan['nama_staf']) ?></p>
					<p class="mb-1"><b>Status:</b>
						<span class="badge bg-<?= e($warna_status[$pesanan['status']]) ?>"><?= e(ucfirst($pesanan['status'])) ?></span>
					</p>
				</div>
			</div>
		</div>
	</div>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th>Produk</th>
							<th>Harga Satuan</th>
							<th>Jumlah</th>
							<th>Subtotal</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($item_pesanan as $it) { ?>
							<tr>
								<td><?= e($it['nama_produk']) ?></td>
								<td><?= rupiah($it['harga_satuan']) ?></td>
								<td><?= e($it['jumlah']) ?></td>
								<td><?= rupiah($it['subtotal']) ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="3" class="text-end">Total</th>
							<th><?= rupiah($pesanan['total']) ?></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
<?php } else { ?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0">Data Pesanan</h2>
		<a href="pesanan.php?aksi=tambah" class="btn btn-primary">+ Buat Pesanan</a>
	</div>
	<form method="get" class="row g-2 mb-3">
		<div class="col-12 col-md-4">
			<input type="text" name="cari" class="form-control" placeholder="Cari nama toko atau kota" value="<?= e($cari) ?>">
		</div>
		<div class="col-12 col-md-2">
			<select name="status" class="form-select">
				<option value="">Semua Status</option>
				<?php foreach ($daftar_status as $s) { ?>
					<option value="<?= e($s) ?>" <?= $status_filter == $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="col-6 col-md-2">
			<input type="date" name="dari" class="form-control" value="<?= e($dari) ?>" title="Dari tanggal">
		</div>
		<div class="col-6 col-md-2">
			<input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>" title="Sampai tanggal">
		</div>
		<div class="col-12 col-md-auto">
			<button type="submit" class="btn btn-outline-primary">Cari</button>
			<a href="pesanan.php" class="btn btn-outline-secondary">Reset</a>
		</div>
	</form>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover align-middle">
					<thead>
						<tr>
							<th><?= link_urut('id', 'No Pesanan', $urut, $arah) ?></th>
							<th><?= link_urut('tanggal', 'Tanggal', $urut, $arah) ?></th>
							<th><?= link_urut('nama_toko', 'Toko', $urut, $arah) ?></th>
							<th><?= link_urut('status', 'Status', $urut, $arah) ?></th>
							<th><?= link_urut('total', 'Total', $urut, $arah) ?></th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($daftar) == 0) { ?>
							<tr>
								<td colspan="6" class="text-center text-muted py-4">Belum ada data pesanan yang cocok.</td>
							</tr>
						<?php } ?>
						<?php foreach ($daftar as $row) { ?>
							<tr>
								<td>#<?= e($row['id']) ?></td>
								<td><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
								<td>
									<?= e($row['nama_toko']) ?><br>
									<small class="text-muted"><?= e($row['kota']) ?></small>
								</td>
								<td>
									<span class="badge bg-<?= e($warna_status[$row['status']]) ?>"><?= e(ucfirst($row['status'])) ?></span>
								</td>
								<td><?= rupiah($row['total']) ?></td>
								<td class="text-nowrap">
									<a href="pesanan.php?aksi=detail&id=<?= e($row['id']) ?>" class="btn btn-sm btn-info">Detail</a>
									<a href="pesanan.php?aksi=ubah&id=<?= e($row['id']) ?>" class="btn btn-sm btn-warning">Ubah</a>
									<?php if ($admin) { ?>
										<form method="post" action="pesanan.php?aksi=hapus" class="form-hapus" data-nama="pesanan #<?= e($row['id']) ?>">
											<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
											<input type="hidden" name="id" value="<?= e($row['id']) ?>">
											<button type="submit" class="btn btn-sm btn-danger">Hapus</button>
										</form>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<p class="text-muted small mb-2">Total: <?= e($total) ?> pesanan</p>
			<?php tampil_halaman($total, $hal, $per_halaman); ?>
		</div>
	</div>
<?php } ?>
<?php include 'footer.php'; ?>