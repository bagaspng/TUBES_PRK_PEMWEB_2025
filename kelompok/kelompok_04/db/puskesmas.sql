-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: puskesmas_db
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `antrian`
--

DROP TABLE IF EXISTS `antrian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `antrian` (
  `id_antrian` int NOT NULL AUTO_INCREMENT,
  `id_jadwal` int NOT NULL,
  `id_pasien` int NOT NULL,
  `nomor_antrian` int NOT NULL,
  `waktu_daftar` datetime NOT NULL,
  `status` enum('menunggu','diperiksa','selesai','batal') NOT NULL DEFAULT 'menunggu',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_antrian`),
  KEY `fk_antrian_jadwal_praktik1_idx` (`id_jadwal`),
  KEY `fk_antrian_pasien1_idx` (`id_pasien`),
  CONSTRAINT `fk_antrian_jadwal_praktik1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_praktik` (`id_jadwal`),
  CONSTRAINT `fk_antrian_pasien1` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `antrian`
--

LOCK TABLES `antrian` WRITE;
/*!40000 ALTER TABLE `antrian` DISABLE KEYS */;
/*!40000 ALTER TABLE `antrian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dokter`
--

DROP TABLE IF EXISTS `dokter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokter` (
  `id_dokter` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_poli` int NOT NULL,
  `kode_dokter` varchar(45) NOT NULL UNIQUE,
  `nama_dokter` varchar(100) NOT NULL,
  `spesialis` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_dokter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dokter`
--

LOCK TABLES `dokter` WRITE;
/*!40000 ALTER TABLE `dokter` DISABLE KEYS */;
/*!40000 ALTER TABLE `dokter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jadwal_praktik`
--

DROP TABLE IF EXISTS `jadwal_praktik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jadwal_praktik` (
  `id_jadwal` int NOT NULL AUTO_INCREMENT,
  `id_dokter` int NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kuota_pasien` int DEFAULT NULL,
  `status` enum('active','unactive') DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_jadwal`),
  KEY `fk_jadwal_praktik_dokter_idx` (`id_dokter`),
  CONSTRAINT `fk_jadwal_praktik_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_praktik`
--

LOCK TABLES `jadwal_praktik` WRITE;
/*!40000 ALTER TABLE `jadwal_praktik` DISABLE KEYS */;
/*!40000 ALTER TABLE `jadwal_praktik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pasien`
--

DROP TABLE IF EXISTS `pasien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pasien` (
  `id_pasien` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `no_rm` varchar(45) NOT NULL UNIQUE,
  `nik` varchar(45) NOT NULL UNIQUE,
  `no_bpjs` VARCHAR(20) NULL UNIQUE,
  `nama_lengkap` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` char(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pasien`
--

LOCK TABLES `pasien` WRITE;
/*!40000 ALTER TABLE `pasien` DISABLE KEYS */;
/*!40000 ALTER TABLE `pasien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengumuman`
--

DROP TABLE IF EXISTS `pengumuman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengumuman` (
  `id_pengumuman` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `gambar` VARCHAR(255) NULL,
  `tanggal` date NOT NULL,
  `status` enum('draft','publish') NOT NULL DEFAULT 'draft',
  `id_admin` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pengumuman`),
  KEY `fk_pengumuman_users1_idx` (`id_admin`),
  CONSTRAINT `fk_pengumuman_users1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengumuman`
--

LOCK TABLES `pengumuman` WRITE;
/*!40000 ALTER TABLE `pengumuman` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengumuman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poli`
--

DROP TABLE IF EXISTS `poli`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `poli` (
  `id_poli` int NOT NULL AUTO_INCREMENT,
  `nama_poli` varchar(100) NOT NULL,
  `deskripsi` varchar(225) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_poli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poli`
--

LOCK TABLES `poli` WRITE;
/*!40000 ALTER TABLE `poli` DISABLE KEYS */;
/*!40000 ALTER TABLE `poli` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rekam_medis`
--

DROP TABLE IF EXISTS `rekam_medis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rekam_medis` (
  `id_rekam` int NOT NULL AUTO_INCREMENT,
  `id_pasien` int NOT NULL,
  `id_dokter` int NOT NULL,
  `id_poli` int NOT NULL,
  `id_antrian` int NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `keluhan` text NOT NULL,
  `riwayat_penyakit` text,
  `pemeriksaan` text,
  `diagnosa` varchar(255) NOT NULL,
  `resep_obat` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `tensi` VARCHAR(50) NOT NULL DEFAULT '-', 
  `suhu` VARCHAR(20) NOT NULL DEFAULT '-', 
  `nadi` VARCHAR(20) NOT NULL DEFAULT '-', 
  `rr` VARCHAR(20) NOT NULL DEFAULT '-',
  PRIMARY KEY (`id_rekam`),
  KEY `fk_rekam_medis_pasien1_idx` (`id_pasien`),
  KEY `fk_rekam_medis_poli1_idx` (`id_poli`),
  KEY `fk_rekam_medis_dokter1_idx` (`id_dokter`),
  KEY `fk_rekam_medis_antrian1_idx` (`id_antrian`),
  CONSTRAINT `fk_rekam_medis_antrian1` FOREIGN KEY (`id_antrian`) REFERENCES `antrian` (`id_antrian`),
  CONSTRAINT `fk_rekam_medis_dokter1` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`),
  CONSTRAINT `fk_rekam_medis_pasien1` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`),
  CONSTRAINT `fk_rekam_medis_poli1` FOREIGN KEY (`id_poli`) REFERENCES `poli` (`id_poli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rekam_medis`
--

LOCK TABLES `rekam_medis` WRITE;
/*!40000 ALTER TABLE `rekam_medis` DISABLE KEYS */;
/*!40000 ALTER TABLE `rekam_medis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rujukan`
--

DROP TABLE IF EXISTS `rujukan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rujukan` (
  `id_rujukan` int NOT NULL AUTO_INCREMENT,
  `id_rekam` int NOT NULL,
  `id_dokter` int NOT NULL,
  `faskes_tujuan` varchar(100) NOT NULL,
  `poli_tujuan` varchar(100) DEFAULT NULL,
  `diagnosa` varchar(255) NOT NULL,
  `tanggal_rujukan` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_rujukan`),
  UNIQUE KEY `id_rekam_UNIQUE` (`id_rekam`),
  KEY `fk_rujukan_dokter1_idx` (`id_dokter`),
  CONSTRAINT `fk_rujukan_dokter1` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`),
  CONSTRAINT `fk_rujukan_rekam_medis1` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rujukan`
--

LOCK TABLES `rujukan` WRITE;
/*!40000 ALTER TABLE `rujukan` DISABLE KEYS */;
/*!40000 ALTER TABLE `rujukan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL,
  `status` enum('active','unactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-05 14:44:10
