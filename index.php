<?php
	require 'config.php';
	cek_login();
	$judul_halaman = "Dasbor";
	$toko_aktif = $db->query("SELECT COUNT(*) FROM toko WHERE aktif = 1")->fetchColumn();
	$pesanan_bulan_ini = $db->query("SELECT COUNT(*) FROM pesanan WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())")->fetchColumn();
	$penjualan_bulan_ini = $db->query("SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE status <> 'batal' AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())")->fetchColumn();
	$total_produk = $db->query("SELECT COUNT(*) FROM produk")->fetchColumn();
	$toko_terbesar = $db->query("SELECT t.nama_toko, t.kota, COUNT(p.id) AS jumlah_pesanan, SUM(p.total) AS total_beli FROM toko t JOIN pesanan p ON p.toko_id = t.id WHERE p.status <> 'batal' GROUP BY t.id ORDER BY total_beli DESC LIMIT 5")->fetchAll();
	$stok_menipis = $db->query("SELECT nama_produk, stok FROM produk WHERE stok <= 5 ORDER BY stok ASC")->fetchAll();
	include 'header.php';
?>

	<h2 class="mb-3">Dasbor</h2>
	<div class="row g-3 mb-4">
		<div class="col-6 col-lg-3">
			<div class="card kartu-info"><div class="card-body">
				<div class="text-muted small">Toko Aktif</div>
				<h3><?= e($toko_aktif) ?></h3>
			</div></div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card kartu-info"><div class="card-body">
				<div class="text-muted small">Pesanan Bulan Ini</div>
				<h3><?= e($pesanan_bulan_ini) ?></h3>
			</div></div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card kartu-info"><div class="card-body">
				<div class="text-muted small">Penjualan Bulan Ini</div>
				<h3><?= rupiah($penjualan_bulan_ini) ?></h3>
			</div></div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card kartu-info"><div class="card-body">
				<div class="text-muted small">Jenis Produk</div>
				<h3><?= e($total_produk) ?></h3>
			</div></div>
		</div>
	</div>
	<div class="row g-3">
		<div class="col-lg-7">
			<div class="card">
				<div class="card-header">Lima Toko dengan Pembelian Terbesar</div>
				<div class="card-body">
					<?php if (count($toko_terbesar) == 0) { ?>
						<p class="text-muted mb-0">Belum ada data pesanan.</p>
					<?php } else { ?>
						<div class="table-responsive">
							<table class="table table-sm">
								<thead>
									<tr>
										<th>Toko</th>
										<th>Kota</th>
										<th>Pesanan</th>
										<th>Total Beli</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($toko_terbesar as $t) { ?>
										<tr>
											<td><?= e($t['nama_toko']) ?></td>
											<td><?= e($t['kota']) ?></td>
											<td><?= e($t['jumlah_pesanan']) ?></td>
											<td><?= rupiah($t['total_beli']) ?></td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="card">
				<div class="card-header">Produk dengan Stok Menipis</div>
				<div class="card-body">
					<?php if (count($stok_menipis) == 0) { ?>
						<p class="text-muted mb-0">Semua stok masih aman.</p>
					<?php } else { ?>
						<ul class="list-group">
							<?php foreach ($stok_menipis as $s) { ?>
								<li class="list-group-item d-flex justify-content-between">
									<?= e($s['nama_produk']) ?>
									<span class="badge bg-danger"><?= e($s['stok']) ?> unit</span>
								</li>
							<?php } ?>
						</ul>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
<?php include 'footer.php'; ?>