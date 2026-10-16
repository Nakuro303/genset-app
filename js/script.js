window.onload = function () {
	var formHapus = document.getElementsByClassName("form-hapus");
	for (var i = 0; i < formHapus.length; i++) {
		formHapus[i].onsubmit = function () {
			var nama = this.getAttribute("data-nama");
			return confirm("Yakin ingin menghapus " + nama + "?");
		};
	}
	var formLogin = document.getElementById("form-login");
	if (formLogin) {
		formLogin.onsubmit = function () {
			var email = document.getElementById("email").value;
			var pola = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!pola.test(email)) {
				alert("Format e-mail tidak valid.");
				return false;
			}
			return true;
		};
	}
	var daftarItem = document.getElementById("daftar-item");
	if (daftarItem) {
		var tombolTambah = document.getElementById("tambah-baris");
		tombolTambah.onclick = function () {
			var baris = daftarItem.getElementsByClassName("baris-item");
			var baru = baris[0].cloneNode(true);
			baru.getElementsByTagName("select")[0].selectedIndex = 0;
			baru.getElementsByTagName("input")[0].value = 1;
			daftarItem.appendChild(baru);
		};
		daftarItem.onclick = function (e) {
			if (e.target.className.indexOf("hapus-baris") != -1) {
				var baris = daftarItem.getElementsByClassName("baris-item");
				if (baris.length > 1) {
					e.target.parentNode.parentNode.remove();
				} else {
					alert("Minimal harus ada satu produk.");
				}
			}
		};
		document.getElementById("form-pesanan").onsubmit = function () {
			var baris = daftarItem.getElementsByClassName("baris-item");
			for (var i = 0; i < baris.length; i++) {
				var pilihan = baris[i].getElementsByTagName("select")[0];
				var jumlah = parseInt(baris[i].getElementsByTagName("input")[0].value);
				var opsi = pilihan.options[pilihan.selectedIndex];
				var stok = parseInt(opsi.getAttribute("data-stok"));
				if (isNaN(jumlah) || jumlah < 1) {
					alert("Jumlah harus lebih dari 0.");
					return false;
				}
				if (jumlah > stok) {
					alert("Stok " + opsi.text + " hanya tersedia " + stok + " unit.");
					return false;
				}
			}
			return true;
		};
	}
};