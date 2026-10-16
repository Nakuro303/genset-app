<?php
	require 'config.php';
	cek_login();
	$dari = $_GET['dari'] ?? date('Y-m-01');
	$sampai = $_GET['sampai'] ?? date('Y-m-d');
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
		$dari = date('Y-m-01');
	}
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
		$sampai = date('Y-m-d');
	}
	$stmt = $db->prepare("SELECT t.nama_toko, t.kota, COUNT(p.id) AS jumlah_pesanan, SUM(p.total) AS total_beli
		FROM toko t
		JOIN pesanan p ON p.toko_id = t.id
		WHERE p.status <> 'batal' AND p.tanggal BETWEEN ? AND ?
		GROUP BY t.id
		ORDER BY total_beli DESC");
	$stmt->execute([$dari, $sampai]);
	$laporan = $stmt->fetchAll();
	if (($_GET['format'] ?? '') == 'csv') {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="laporan_penjualan_' . $dari . '_' . $sampai . '.csv"');
		$out = fopen('php://output', 'w');
		fputcsv($out, array('No', 'Nama Toko', 'Kota', 'Jumlah Pesanan', 'Total Pembelian'));
		$no = 1;
		foreach ($laporan as $row) {
			fputcsv($out, array($no++, $row['nama_toko'], $row['kota'], $row['jumlah_pesanan'], $row['total_beli']));
		}
		fclose($out);
		exit;
	}
	$judul_halaman = "Laporan Penjualan";
	include 'header.php';
?>

	<h2 class="mb-3">Laporan Penjualan per Toko</h2>
	<form method="get" class="row g-2 mb-3 d-print-none">
		<div class="col-6 col-md-3">
			<label class="form-label">Dari tanggal</label>
			<input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
		</div>
		<div class="col-6 col-md-3">
			<label class="form-label">Sampai tanggal</label>
			<input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
		</div>
		<div class="col-12 col-md-auto d-flex align-items-end">
			<button type="submit" class="btn btn-primary me-2">Tampilkan</button>
			<a href="laporan.php?format=csv&dari=<?= e($dari) ?>&sampai=<?= e($sampai) ?>" class="btn btn-success me-2">Unduh CSV</a>
			<button type="button" class="btn btn-secondary" onclick="window.print()">Cetak</button>
		</div>
	</form>
	<p>Periode: <b><?= e(date('d-m-Y', strtotime($dari))) ?></b> sampai <b><?= e(date('d-m-Y', strtotime($sampai))) ?></b> (pesanan batal tidak dihitung)</p>
	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th>No</th>
							<th>Nama Toko</th>
							<th>Kota</th>
							<th>Jumlah Pesanan</th>
							<th>Total Pembelian</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($laporan) == 0) { ?>
							<tr>
								<td colspan="5" class="text-center text-muted py-4">Tidak ada penjualan pada periode ini.</td>
							</tr>
						<?php } ?>
						<?php $no = 1; $jumlah_semua = 0; $total_semua = 0; ?>
						<?php foreach ($laporan as $row) { ?>
							<?php $jumlah_semua += $row['jumlah_pesanan']; $total_semua += $row['total_beli']; ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= e($row['nama_toko']) ?></td>
								<td><?= e($row['kota']) ?></td>
								<td><?= e($row['jumlah_pesanan']) ?></td>
								<td><?= rupiah($row['total_beli']) ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<?php if (count($laporan) > 0) { ?>
						<tfoot>
							<tr>
								<th colspan="3" class="text-end">Jumlah</th>
								<th><?= e($jumlah_semua) ?></th>
								<th><?= rupiah($total_semua) ?></th>
							</tr>
						</tfoot>
					<?php } ?>
				</table>
			</div>
		</div>
	</div>
<?php include 'footer.php'; ?>