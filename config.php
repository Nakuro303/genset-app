<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$nama_db = "db_genset";
try {
	$db = new PDO("mysql:host=$host;dbname=$nama_db;charset=utf8mb4", $user, $pass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	die("Koneksi database gagal. Cek pengaturan di config.php");
}

function e($teks)
{
	return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
	return "Rp " . number_format($angka, 0, ',', '.');
}

function cek_login()
{
	if (!isset($_SESSION['pengguna'])) {
		header("Location: login.php");
		exit;
	}
}

function cek_admin()
{
	cek_login();
	if ($_SESSION['pengguna']['peran'] != 'admin') {
		set_pesan('danger', 'Halaman ini hanya bisa dibuka oleh Admin.');
		header("Location: index.php");
		exit;
	}
}

function buat_token()
{
	if (!isset($_SESSION['token'])) {
		$_SESSION['token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['token'];
}

function cek_token()
{
	$token_kiriman = $_POST['token'] ?? '';
	if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $token_kiriman)) {
		die("Token tidak valid. Silakan kembali dan muat ulang halaman.");
	}
}

function set_pesan($jenis, $teks)
{
	$_SESSION['pesan'] = array('jenis' => $jenis, 'teks' => $teks);
}

function tampil_pesan()
{
	if (isset($_SESSION['pesan'])) {
		$p = $_SESSION['pesan'];
		echo '<div class="alert alert-' . e($p['jenis']) . ' alert-dismissible fade show" role="alert">';
		echo e($p['teks']);
		echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
		echo '</div>';
		unset($_SESSION['pesan']);
	}
}

function link_urut($kolom, $label, $urut, $arah)
{
	$arah_baru = ($urut == $kolom && $arah == 'ASC') ? 'desc' : 'asc';
	$query = $_GET;
	$query['urut'] = $kolom;
	$query['arah'] = $arah_baru;
	$query['hal'] = 1;

	$panah = '';
	if ($urut == $kolom) {
		$panah = ($arah == 'ASC') ? ' ▲' : ' ▼';
	}
	return '<a href="?' . e(http_build_query($query)) . '" class="link-urut">' . e($label) . $panah . '</a>';
}

function tampil_halaman($total, $hal, $per_halaman)
{
	$jumlah_hal = ceil($total / $per_halaman);
	if ($jumlah_hal <= 1) {
		return;
	}

	echo '<nav><ul class="pagination flex-wrap justify-content-center">';

	$query = $_GET;
	if ($hal > 1) {
		$query['hal'] = $hal - 1;
		echo '<li class="page-item"><a class="page-link" href="?' . e(http_build_query($query)) . '">Sebelumnya</a></li>';
	}

	$awal = max(1, $hal - 2);
	$akhir = min($jumlah_hal, $hal + 2);
	for ($i = $awal; $i <= $akhir; $i++) {
		$query['hal'] = $i;
		$aktif = ($i == $hal) ? 'active' : '';
		echo '<li class="page-item ' . $aktif . '"><a class="page-link" href="?' . e(http_build_query($query)) . '">' . $i . '</a></li>';
	}

	if ($hal < $jumlah_hal) {
		$query['hal'] = $hal + 1;
		echo '<li class="page-item"><a class="page-link" href="?' . e(http_build_query($query)) . '">Berikutnya</a></li>';
	}

	echo '</ul></nav>';
}