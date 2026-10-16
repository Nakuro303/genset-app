CREATE DATABASE IF NOT EXISTS db_genset CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_genset;

DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS toko;
DROP TABLE IF EXISTS pengguna;

CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    peran ENUM('admin','staf') NOT NULL DEFAULT 'staf',
    dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE toko (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_toko VARCHAR(100) NOT NULL,
    nama_pemilik VARCHAR(100) NOT NULL,
    alamat VARCHAR(255) NOT NULL,
    kota VARCHAR(60) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    harga INT UNSIGNED NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    toko_id INT NOT NULL,
    pengguna_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('baru','diproses','selesai','batal') NOT NULL DEFAULT 'baru',
    total BIGINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (toko_id) REFERENCES toko(id) ON DELETE RESTRICT,
    FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    harga_satuan INT UNSIGNED NOT NULL,
    subtotal BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO pengguna (nama, email, password_hash, peran) VALUES
('Administrator', 'admin@gmail.com', '$2y$10$ElEY0Z2SLTs/INy48M9rqOEFIUXRBNwncQEz7xB.kVAcN7SlDuRau', 'admin'),
('Budi Staf Penjualan', 'staf@gmail.com', '$2y$10$.C3r7lbZ1Z8Do3FcsBVNYePfBmiHtseO59V171S8J.D3qrGPcOg0y', 'staf');

INSERT INTO toko (nama_toko, nama_pemilik, alamat, kota, no_telepon, email) VALUES
('Toko Sinar Jaya', 'Hendra Wijaya', 'Jl. Gajah Mada No. 12', 'Jakarta', '021555101', 'sinarjaya@gmail.com'),
('Toko Maju Teknik', 'Siti Rahayu', 'Jl. Asia Afrika No. 45', 'Bandung', '022555102', 'majuteknik@gmail.com'),
('Toko Mesin Abadi', 'Agus Santoso', 'Jl. Pemuda No. 8', 'Semarang', '024555103', 'mesinabadi@gmail.com'),
('Toko Listrik Mandiri', 'Dewi Lestari', 'Jl. Basuki Rahmat No. 77', 'Surabaya', '031555104', 'listrikmandiri@gmail.com'),
('Toko Tani Makmur', 'Joko Susilo', 'Jl. Diponegoro No. 3', 'Yogyakarta', '0274555105', 'tanimakmur@gmail.com'),
('Toko Berkah Motor', 'Rudi Hartono', 'Jl. Sudirman No. 21', 'Medan', '061555106', 'berkahmotor@gmail.com'),
('Toko Cahaya Terang', 'Linda Kusuma', 'Jl. Veteran No. 90', 'Makassar', '0411555107', 'cahayaterang@gmail.com'),
('Toko Sumber Rejeki', 'Bambang Pratama', 'Jl. Ahmad Yani No. 15', 'Palembang', '0711555108', 'sumberrejeki@gmail.com'),
('Toko Harapan Baru', 'Rina Marlina', 'Jl. Merdeka No. 33', 'Bandung', '022555109', 'harapanbaru@gmail.com'),
('Toko Karya Mandiri', 'Eko Prasetyo', 'Jl. Pahlawan No. 6', 'Malang', '0341555110', 'karyamandiri@gmail.com'),
('Toko Surya Elektrik', 'Yanti Susanti', 'Jl. Imam Bonjol No. 52', 'Denpasar', '0361555111', 'suryaelektrik@gmail.com'),
('Toko Global Power', 'Andi Firmansyah', 'Jl. Thamrin No. 19', 'Jakarta', '021555112', 'globalpower@gmail.com');

INSERT INTO produk (nama_produk, harga, stok) VALUES
('Genset Silent 2 KVA', 4500000, 20),
('Genset Bensin 3 KVA', 6200000, 15),
('Genset Diesel 5 KVA', 14500000, 8),
('Genset Diesel 10 KVA', 27800000, 4),
('Genset Silent 15 KVA', 41000000, 3),
('Genset Portable 1 KVA', 2300000, 30);

INSERT INTO pesanan (toko_id, pengguna_id, tanggal, status, total) VALUES
(1, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'selesai', 15900000),
(2, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'diproses', 29000000),
(3, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'baru', 40200000),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'selesai', 41000000),
(5, 2, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'selesai', 18600000),
(4, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'batal', 22500000),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'selesai', 23000000);

INSERT INTO detail_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal) VALUES
(1, 1, 2, 4500000, 9000000),
(1, 6, 3, 2300000, 6900000),
(2, 3, 2, 14500000, 29000000),
(3, 4, 1, 27800000, 27800000),
(3, 2, 2, 6200000, 12400000),
(4, 5, 1, 41000000, 41000000),
(5, 2, 3, 6200000, 18600000),
(6, 1, 5, 4500000, 22500000),
(7, 6, 10, 2300000, 23000000);