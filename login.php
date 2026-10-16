<?php
	require 'config.php';
	if (($_GET['aksi'] ?? '') == 'logout') {
		session_unset();
		session_destroy();
		session_start();
		set_pesan('success', 'Anda sudah logout.');
		header("Location: login.php");
		exit;
	}
	if (isset($_SESSION['pengguna'])) {
		header("Location: index.php");
		exit;
	}
	$error = "";
	$email = "";
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		cek_token();
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		if (($_SESSION['gagal_login'] ?? 0) >= 5 && time() >= $_SESSION['kunci_sampai']) {
			$_SESSION['gagal_login'] = 0;
		}
		if (($_SESSION['gagal_login'] ?? 0) >= 5) {
			$sisa = $_SESSION['kunci_sampai'] - time();
			$error = "Terlalu banyak percobaan gagal. Coba lagi dalam " . $sisa . " detik.";
		} elseif ($email == "" && $password == "") {
			set_pesan('warning', 'Anda belum memasukkan email dan password');
		} elseif ($email == "") {
			set_pesan('warning', 'Anda belum memasukkan email');
		} elseif ($password == "") {
			set_pesan('warning', 'Anda belum memasukkan password');
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = "Format e-mail tidak valid.";
		} else {
			$stmt = $db->prepare("SELECT * FROM pengguna WHERE email = ?");
			$stmt->execute([$email]);
			$data = $stmt->fetch();
			if ($data && password_verify($password, $data['password_hash'])) {
				session_regenerate_id(true);
				$_SESSION['pengguna'] = array(
					'id' => $data['id'],
					'nama' => $data['nama'],
					'email' => $data['email'],
					'peran' => $data['peran']
				);
				$_SESSION['gagal_login'] = 0;
				header("Location: index.php");
				exit;
			} else {
				$_SESSION['gagal_login'] = ($_SESSION['gagal_login'] ?? 0) + 1;
				if ($_SESSION['gagal_login'] >= 5) {
					$_SESSION['kunci_sampai'] = time() + 300;
				}
				$error = "E-mail atau password salah.";
			}
		}
	}
?>

<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Login - Aplikasi Genset</title>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.css">
	</head>
	<body>
		<div class="container">
			<div class="kotak-login">
				<?php tampil_pesan(); ?>
				<div class="card shadow-sm">
					<div class="card-body p-4">
						<h3 class="text-center mb-4">Login</h3>
						<?php if ($error != "") { ?>
							<div class="alert alert-danger"><?= e($error) ?></div>
						<?php } ?>
						<form method="post" id="form-login">
							<input type="hidden" name="token" value="<?= e(buat_token()) ?>">
							<div class="mb-3">
								<label for="email" class="form-label">E-mail</label>
								<input type="email" name="email" id="email" class="form-control" value="<?= e($email) ?>">
							</div>
							<div class="mb-3">
								<label for="password" class="form-label">Password</label>
								<input type="password" name="password" id="password" class="form-control">
							</div>
							<button type="submit" class="btn btn-primary w-100">Masuk</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script src="js/bootstrap.bundle.min.js"></script>
	</body>
</html>