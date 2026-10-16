<?php
	$peran = $_SESSION['pengguna']['peran'];
	$halaman_ini = basename($_SERVER['PHP_SELF']);
?>

<html>
	<head>
		<title><?= e($judul_halaman) ?> - Toko Genset</title>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.css">
	</head>
	<body class="d-flex flex-column min-vh-100">
		<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-print-none">
			<div class="container-fluid">
				<a class="navbar-brand order-0" href="index.php">Shishiron Genset</a>
				<div class="d-flex align-items-center order-1 order-lg-2">
					<span class="text-white me-2 d-none d-md-inline">
						<?= e($_SESSION['pengguna']['nama']) ?> (<?= e($peran) ?>)
					</span>
					<a href="login.php?aksi=logout" class="btn btn-outline-light btn-sm me-2">Logout</a>
					<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
						<span class="navbar-toggler-icon"></span>
					</button>
				</div>
				<div class="collapse navbar-collapse order-2 order-lg-1" id="menuUtama">
					<ul class="navbar-nav me-auto">
						<li class="nav-item">
							<a class="nav-link <?= $halaman_ini == 'index.php' ? 'active' : '' ?>" href="index.php">Dasbor</a>
						</li>
						<li class="nav-item">
							<a class="nav-link <?= $halaman_ini == 'toko.php' ? 'active' : '' ?>" href="toko.php">Toko</a>
						</li>
						<li class="nav-item">
							<a class="nav-link <?= $halaman_ini == 'pesanan.php' ? 'active' : '' ?>" href="pesanan.php">Pesanan</a>
						</li>
						<?php if ($peran == 'admin') { ?>
							<li class="nav-item">
								<a class="nav-link <?= $halaman_ini == 'produk.php' ? 'active' : '' ?>" href="produk.php">Produk</a>
							</li>
							<li class="nav-item">
								<a class="nav-link <?= $halaman_ini == 'pengguna.php' ? 'active' : '' ?>" href="pengguna.php">Pengguna</a>
							</li>
						<?php } ?>
						<li class="nav-item">
							<a class="nav-link <?= $halaman_ini == 'laporan.php' ? 'active' : '' ?>" href="laporan.php">Laporan</a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="container my-4">
	<?php tampil_pesan(); ?>