-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Sep 2026 pada 13.54
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_laporan_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `activity_date` date NOT NULL,
  `status` enum('PENDING','DISETUJUI','DITOLAK') DEFAULT 'PENDING',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `title`, `description`, `activity_date`, `status`, `verified_by`, `verified_at`, `rejection_note`, `created_at`, `updated_at`) VALUES
(1, 3, 'Pelajaran Matematika Kelas X', 'Melakukan kegiatan mengajar matematika untuk kelas X IPA 1 dan X IPA 2', '2024-01-15', 'DISETUJUI', NULL, NULL, NULL, '2026-09-25 11:15:59', '2026-09-25 11:15:59'),
(2, 3, 'Rapat Wali Murid', 'Menghadiri rapat wali murid kelas X', '2024-01-16', 'DISETUJUI', 1, '2026-09-25 12:42:09', NULL, '2026-09-25 11:15:59', '2026-09-25 12:42:09'),
(3, 4, 'Pengelolaan Dokumen Siswa', 'Mengurus dokumen administrasi siswa baru', '2024-01-15', 'DISETUJUI', NULL, NULL, NULL, '2026-09-25 11:15:59', '2026-09-25 11:15:59'),
(4, 4, 'Persiapan Ujian Semester', 'Menyiapkan berkas-berkas ujian semester', '2024-01-17', 'DISETUJUI', 1, '2026-09-26 11:10:49', NULL, '2026-09-25 11:15:59', '2026-09-26 11:10:49'),
(5, 3, 'Koreksi Ujian Tengah Semester', 'Mengoreksi hasil ujian tengah semester kelas X dan XI', '2024-01-18', 'DISETUJUI', NULL, NULL, NULL, '2026-09-25 11:15:59', '2026-09-25 11:15:59'),
(6, 4, 'Penginputan Nilai', 'Menginput nilai siswa ke sistem sekolah', '2024-01-19', 'DISETUJUI', NULL, NULL, NULL, '2026-09-25 11:15:59', '2026-09-25 11:15:59'),
(7, 3, 'pelepasan Kelas 3', 'perpisahan sekolah', '2026-09-26', 'DISETUJUI', 1, '2026-09-26 11:12:03', NULL, '2026-09-26 11:11:32', '2026-09-26 11:12:03'),
(8, 4, 'Penampahan meja baru', 'diterima meja baru sejumalh 30 pcs', '2026-09-26', 'DISETUJUI', 1, '2026-09-26 11:14:16', NULL, '2026-09-26 11:13:49', '2026-09-26 11:14:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('ADMIN','GURU','PEGAWAI','KEPALA_SEKOLAH') DEFAULT 'GURU',
  `nip` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `role`, `nip`, `phone`, `position`, `address`, `gender`, `birth_date`, `created_at`, `updated_at`) VALUES
(1, 'admin@sekolah.com', 'admin123', 'Administrator', 'ADMIN', '198501012010011001', '', 'Administrator Sistem', '', '', '0000-00-00', '2026-09-25 11:15:59', '2026-09-26 11:19:15'),
(2, 'kepala@sekolah.com', 'kepsek123', 'Dr. Ahmad Sulaiman, M.Pd', 'KEPALA_SEKOLAH', '196001011985031001', '0812345678090', 'Kepala Sekolah', '', 'Laki-laki', '0000-00-00', '2026-09-25 11:15:59', '2026-09-26 11:40:00'),
(3, 'guru@sekolah.com', 'guru123', 'Budi Santoso', 'GURU', '198501012010011001', '081234567890', 'Guru Matematika', '', 'Laki-laki', '0000-00-00', '2026-09-25 11:15:59', '2026-09-26 11:28:30'),
(4, 'pegawai@sekolah.com', 'pegawai123', 'Siti Rahayu wijaya', 'PEGAWAI', '199001012015012001', '081234567891', 'Staff Tata Usaha', '', 'Perempuan', '0000-00-00', '2026-09-25 11:15:59', '2026-09-26 11:33:39');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
