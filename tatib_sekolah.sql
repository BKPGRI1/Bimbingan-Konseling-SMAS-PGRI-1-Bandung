-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Sep 2026 pada 17.37
-- Versi server: 10.1.38-MariaDB
-- Versi PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tatib_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('H','I','A','S') DEFAULT 'H',
  `keterangan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `kelas_id`, `tanggal`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(30, 24, 6, '2026-09-07', 'H', '', '2026-09-06 13:46:11', NULL),
(36, 24, 6, '2026-09-06', 'A', '', '2026-09-06 13:48:32', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan_konseling`
--

CREATE TABLE `catatan_konseling` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `bk_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jenis_konseling` enum('individu','kelompok','kelas') DEFAULT 'individu',
  `topik` varchar(255) DEFAULT NULL,
  `catatan` text NOT NULL,
  `tindak_lanjut` text,
  `status` enum('pending','proses','selesai') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatan_pembinaan`
--

CREATE TABLE `catatan_pembinaan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `wali_kelas_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `catatan` text NOT NULL,
  `tindak_lanjut` text,
  `status` enum('pending','selesai') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hari_libur`
--

CREATE TABLE `hari_libur` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `hari` varchar(20) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `jenis` enum('otomatis_harian','otomatis_tanggal','custom') DEFAULT 'custom',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `hari_libur`
--

INSERT INTO `hari_libur` (`id`, `tanggal`, `hari`, `keterangan`, `jenis`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Minggu', 'Libur Mingguan', 'otomatis_harian', 1, '2026-09-06 13:29:59', NULL),
(2, NULL, 'Sabtu', 'Libur', 'otomatis_harian', 1, '2026-09-06 13:30:14', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_pelanggaran`
--

CREATE TABLE `jenis_pelanggaran` (
  `id` int(11) NOT NULL,
  `nama_pelanggaran` varchar(100) NOT NULL,
  `poin` int(11) NOT NULL,
  `kategori` enum('ringan','sedang','berat') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `jenis_pelanggaran`
--

INSERT INTO `jenis_pelanggaran` (`id`, `nama_pelanggaran`, `poin`, `kategori`, `is_active`, `created_at`) VALUES
(3, 'Merokok', 50, 'berat', 1, '2026-07-11 13:57:22'),
(4, 'Berkelahi', 75, 'berat', 1, '2026-07-11 13:57:22'),
(5, 'Membawa HP', 20, 'sedang', 1, '2026-07-11 13:57:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `tingkat` varchar(10) DEFAULT NULL,
  `jurusan` varchar(20) DEFAULT NULL,
  `wali_kelas_id` int(11) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `wali_kelas_id`, `tahun_ajaran`, `created_at`) VALUES
(6, '7', 'A', 'IPA', 3, '2026/2027', '2026-08-02 17:13:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_poin`
--

CREATE TABLE `log_poin` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `perubahan` int(11) NOT NULL,
  `keterangan` text,
  `jenis` enum('pelanggaran','prestasi','prestasi_edit','prestasi_hapus','manual') DEFAULT 'manual',
  `pelanggaran_id` int(11) DEFAULT NULL,
  `prestasi_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggaran`
--

CREATE TABLE `pelanggaran` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `jenis_pelanggaran_id` int(11) NOT NULL,
  `guru_id` int(11) DEFAULT NULL,
  `tanggal_pelanggaran` date NOT NULL,
  `jam_pelanggaran` time DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `keterangan` text,
  `bukti` varchar(255) DEFAULT NULL,
  `poin_diperoleh` int(11) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `alamat` text,
  `no_telp` varchar(15) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `batas_poin_peringatan` int(11) DEFAULT '100',
  `batas_poin_pemanggilan` int(11) DEFAULT '200',
  `batas_poin_do` int(11) DEFAULT '300',
  `api_wa_url` varchar(255) DEFAULT NULL,
  `api_wa_token` varchar(100) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `logo`, `alamat`, `no_telp`, `email`, `batas_poin_peringatan`, `batas_poin_pemanggilan`, `batas_poin_do`, `api_wa_url`, `api_wa_token`, `tahun_ajaran`) VALUES
(1, 'SMA Viralytics_Shop', 'logo_20260802_173758_6a6f1e0617eed.png', 'Jl. Probolinggo', '021-1234567', 'infoSMA_Viralytics_Shop.sch.id@gmail.com', 100, 125, 300, 'https://api.sidobe.com/wa/v1/send-message', 'GIeeuuiGoNEwokyWXpsRMWySASuoSfUFfSpBbolqihNpiaeecK', '2026/2027');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_surat`
--

CREATE TABLE `pengaturan_surat` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(255) DEFAULT NULL,
  `alamat_sekolah` text,
  `no_telp_sekolah` varchar(20) DEFAULT NULL,
  `email_sekolah` varchar(100) DEFAULT NULL,
  `website_sekolah` varchar(100) DEFAULT NULL,
  `kementrian` varchar(255) DEFAULT NULL,
  `logo_kiri` varchar(255) DEFAULT NULL,
  `logo_kanan` varchar(255) DEFAULT NULL,
  `stempel_sekolah` varchar(255) DEFAULT NULL,
  `kepala_sekolah` varchar(255) DEFAULT NULL,
  `nip_kepala_sekolah` varchar(50) DEFAULT NULL,
  `ttd_kepala_sekolah` varchar(255) DEFAULT NULL,
  `ttd_kepala_sekolah_nama` varchar(255) DEFAULT NULL,
  `nomor_surat` varchar(50) DEFAULT NULL,
  `pembukaan_teguran` text,
  `isi_teguran` text,
  `penutup_teguran` text,
  `pembukaan_pemanggilan` text,
  `isi_pemanggilan` text,
  `penutup_pemanggilan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `pengaturan_surat`
--

INSERT INTO `pengaturan_surat` (`id`, `nama_sekolah`, `alamat_sekolah`, `no_telp_sekolah`, `email_sekolah`, `website_sekolah`, `kementrian`, `logo_kiri`, `logo_kanan`, `stempel_sekolah`, `kepala_sekolah`, `nip_kepala_sekolah`, `ttd_kepala_sekolah`, `ttd_kepala_sekolah_nama`, `nomor_surat`, `pembukaan_teguran`, `isi_teguran`, `penutup_teguran`, `pembukaan_pemanggilan`, `isi_pemanggilan`, `penutup_pemanggilan`, `created_at`, `updated_at`) VALUES
(1, 'SMA Viralytics_Shop', 'Jl. Probolinggo No. 123, Probolinggo', '082131290542', 'info@smaviralytics.sch.id', 'www.smaviralytics.sch.id', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi', 'logo_kiri_20260713_234240.png', 'logo_kanan_20260713_234240.png', 'stempel_20260713_234240.png', 'Dr. Viralytics, M.Pd.', '196504251990011001', 'ttd_20260713_234925.png', 'Dr. Viralytics, M.Pd.', '001/TEG/2024', 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa', 'Berdasarkan hasil pengamatan dan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu:\r\n\r\nNama Siswa    : {nama_siswa}\r\nNISN          : {nisn}\r\nKelas         : {kelas}\r\n\r\nTelah teridentifikasi melakukan pelanggaran tata tertib sekolah dengan total akumulasi poin sebesar {poin} poin.\r\n\r\nSehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan dan peringatan awal. Kami berharap putra/putri Bapak/Ibu dapat memperbaiki sikap dan perilakunya serta tidak mengulangi pelanggaran di kemudian hari.\r\n\r\nKami sangat mengharapkan peran serta Bapak/Ibu untuk memberikan pengawasan dan bimbingan kepada putra/putri Bapak/Ibu di lingkungan keluarga.', 'Demikian Surat Teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.', 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa', 'Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami mengundang Bapak/Ibu untuk dapat hadir di sekolah guna membahas permasalahan putra/putri Bapak/Ibu:\r\n\r\nNama Siswa    : {nama_siswa}\r\nNISN          : {nisn}\r\nKelas         : {kelas}\r\nTotal Poin    : {poin} poin\r\n\r\nUndangan ini kami sampaikan mengingat akumulasi poin pelanggaran putra/putri Bapak/Ibu telah mencapai batas yang memerlukan tindak lanjut dari orang tua/wali.\r\n\r\nKami mohon Bapak/Ibu dapat hadir pada:\r\nHari/Tanggal  : {tanggal_pemanggilan}\r\nWaktu         : {jam_pemanggilan}\r\nTempat        : Ruang Bimbingan Konseling (BK)\r\n\r\nKehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik dalam pembinaan dan pembentukan karakter putra/putri Bapak/Ibu.', 'Demikian Surat Pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.', '2026-07-13 16:39:20', '2026-07-13 16:59:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_surat_backup`
--

CREATE TABLE `pengaturan_surat_backup` (
  `id` int(11) NOT NULL DEFAULT '0',
  `nama_sekolah` varchar(255) DEFAULT NULL,
  `alamat_sekolah` text,
  `no_telp_sekolah` varchar(20) DEFAULT NULL,
  `email_sekolah` varchar(100) DEFAULT NULL,
  `website_sekolah` varchar(100) DEFAULT NULL,
  `kementrian` varchar(255) DEFAULT NULL,
  `logo_kiri` varchar(255) DEFAULT NULL,
  `logo_kanan` varchar(255) DEFAULT NULL,
  `stempel_sekolah` varchar(255) DEFAULT NULL,
  `kepala_sekolah` varchar(255) DEFAULT NULL,
  `nip_kepala_sekolah` varchar(50) DEFAULT NULL,
  `ttd_kepala_sekolah` varchar(255) DEFAULT NULL,
  `ttd_kepala_sekolah_nama` varchar(255) DEFAULT NULL,
  `nomor_surat` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `pembukaan_teguran` text,
  `isi_teguran` text,
  `penutup_teguran` text,
  `pembukaan_pemanggilan` text,
  `isi_pemanggilan` text,
  `penutup_pemanggilan` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `pengaturan_surat_backup`
--

INSERT INTO `pengaturan_surat_backup` (`id`, `nama_sekolah`, `alamat_sekolah`, `no_telp_sekolah`, `email_sekolah`, `website_sekolah`, `kementrian`, `logo_kiri`, `logo_kanan`, `stempel_sekolah`, `kepala_sekolah`, `nip_kepala_sekolah`, `ttd_kepala_sekolah`, `ttd_kepala_sekolah_nama`, `nomor_surat`, `created_at`, `updated_at`, `pembukaan_teguran`, `isi_teguran`, `penutup_teguran`, `pembukaan_pemanggilan`, `isi_pemanggilan`, `penutup_pemanggilan`) VALUES
(1, 'SMA Viralytics_Shop', 'Jl. Probolinggo', '021-1234567', 'junaidisahendra@gmail.com', 'asas', 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi', 'surat_20260713_101600_6a545870ef200.png', 'surat_20260713_101600_6a545870ef592.png', 'surat_20260713_101600_6a545870ef8c9.png', '', '', 'surat_20260713_101600_6a545870efc90.png', '', '001/TEG/2024', '2026-07-13 02:04:13', '2026-07-13 03:45:49', 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.', 'Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, {nama_siswa} dengan NISN {nisn} dari kelas {kelas} telah melakukan beberapa pelanggaran dengan total akumulasi poin sebanyak {poin} poin.\r\n\r\nSehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan agar siswa yang bersangkutan dapat memperbaiki sikap dan tidak mengulangi pelanggaran yang sama di kemudian hari.\r\n\r\nKami mengharapkan kerja sama dari orang tua/wali untuk turut melakukan pembinaan dan pengawasan terhadap putra/putrinya.', 'Demikian surat teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.', 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.', 'Dengan ini kami mengundang Bapak/Ibu untuk hadir di sekolah pada:\r\n\r\nHari/Tanggal : {tanggal_pemanggilan}\r\nJam          : {jam_pemanggilan}\r\nTempat       : Ruang BK\r\n\r\nuntuk membahas masalah pelanggaran yang dilakukan oleh {nama_siswa} dengan NISN {nisn} dari kelas {kelas} yang telah mencapai total poin {poin}.\r\n\r\nKehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik bagi putra/putri Bapak/Ibu.', 'Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `prestasi_siswa`
--

CREATE TABLE `prestasi_siswa` (
  `id` int(11) NOT NULL,
  `no_prestasi` varchar(50) DEFAULT NULL,
  `siswa_id` int(11) NOT NULL,
  `jenis_prestasi` enum('akademik','non_akademik','lainnya') NOT NULL DEFAULT 'akademik',
  `nama_prestasi` varchar(255) NOT NULL,
  `tingkat` enum('sekolah','kecamatan','kota','provinsi','nasional','internasional') DEFAULT 'sekolah',
  `juara` varchar(50) DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `poin_prestasi` int(11) NOT NULL DEFAULT '10',
  `keterangan` text,
  `sertifikat` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text,
  `no_hp` varchar(15) DEFAULT NULL,
  `poin` int(11) DEFAULT '0',
  `foto` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `nisn`, `nama_siswa`, `kelas_id`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_hp`, `poin`, `foto`, `created_at`) VALUES
(24, '1234567891', 'Siti Rahayu', 6, 'P', 'Bandung', '0000-00-00', 'Jl. Asia Afrika No. 12, Bandung', '81234567891', 0, NULL, '2026-09-01 19:51:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `surat_meneg`
--

CREATE TABLE `surat_meneg` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `jenis_surat` enum('teguran','pemanggilan','peringatan','do') DEFAULT 'teguran',
  `nomor_surat` varchar(50) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `isi_surat` text NOT NULL,
  `status` enum('draft','terkirim','selesai') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nip` varchar(20) DEFAULT NULL,
  `role` enum('admin','guru','bk','walikelas') DEFAULT 'guru',
  `jabatan` varchar(50) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `is_piket` tinyint(1) DEFAULT '0',
  `is_bk` tinyint(1) DEFAULT '0',
  `is_walikelas` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `nip`, `role`, `jabatan`, `no_hp`, `foto`, `is_piket`, `is_bk`, `is_walikelas`, `created_at`) VALUES
(1, 'admin', 'c93ccd78b2076528346216b3b2f701e6', 'Administrator', NULL, 'admin', 'Admin', NULL, NULL, 0, 0, 0, '2026-07-11 13:57:22'),
(2, 'lily123', '84df58f4d40fc0c09de7829bc676c22f', 'lily123', '1', 'guru', 'Guru IPA', '081234567892', NULL, 0, 0, 0, '2026-07-14 14:36:45'),
(3, 'santoso1234', '150bd3149d7ce75aceb80367209e05cb', 'Budi Santoso', '197406022005011004', 'walikelas', '', '', NULL, 0, 0, 1, '2026-07-14 16:01:48'),
(4, 'Lutfillah', 'ce07e06a8165477571413269a288055f', 'Lutfillah', '20240001', 'bk', '', '', NULL, 0, 0, 0, '2026-07-14 16:02:33'),
(5, '20240001', '$2y$10$VQux5yhKpvHcfmd7bu5oDuoSnFpb3GYCzv4pqC8Rac6d2TKZtWyUy', 'Lutfillah', '20240001', '', NULL, NULL, NULL, 0, 0, 0, '2026-08-02 17:14:14');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indeks untuk tabel `catatan_konseling`
--
ALTER TABLE `catatan_konseling`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `bk_id` (`bk_id`);

--
-- Indeks untuk tabel `catatan_pembinaan`
--
ALTER TABLE `catatan_pembinaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `wali_kelas_id` (`wali_kelas_id`);

--
-- Indeks untuk tabel `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `wali_kelas_id` (`wali_kelas_id`);

--
-- Indeks untuk tabel `log_poin`
--
ALTER TABLE `log_poin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `jenis_pelanggaran_id` (`jenis_pelanggaran_id`),
  ADD KEY `guru_id` (`guru_id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengaturan_surat`
--
ALTER TABLE `pengaturan_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indeks untuk tabel `surat_meneg`
--
ALTER TABLE `surat_meneg`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `catatan_konseling`
--
ALTER TABLE `catatan_konseling`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `catatan_pembinaan`
--
ALTER TABLE `catatan_pembinaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `log_poin`
--
ALTER TABLE `log_poin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengaturan_surat`
--
ALTER TABLE `pengaturan_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `surat_meneg`
--
ALTER TABLE `surat_meneg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `catatan_konseling`
--
ALTER TABLE `catatan_konseling`
  ADD CONSTRAINT `catatan_konseling_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catatan_konseling_ibfk_2` FOREIGN KEY (`bk_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `catatan_pembinaan`
--
ALTER TABLE `catatan_pembinaan`
  ADD CONSTRAINT `catatan_pembinaan_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catatan_pembinaan_ibfk_2` FOREIGN KEY (`wali_kelas_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`wali_kelas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `log_poin`
--
ALTER TABLE `log_poin`
  ADD CONSTRAINT `log_poin_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD CONSTRAINT `pelanggaran_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pelanggaran_ibfk_2` FOREIGN KEY (`jenis_pelanggaran_id`) REFERENCES `jenis_pelanggaran` (`id`),
  ADD CONSTRAINT `pelanggaran_ibfk_3` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  ADD CONSTRAINT `prestasi_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `surat_meneg`
--
ALTER TABLE `surat_meneg`
  ADD CONSTRAINT `surat_meneg_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
