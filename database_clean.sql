-- MySQL dump for KAsql Clean Database
-- Project: KAsql - Monorepo Accounting System
-- Target Database: komputer_akuntan
-- ------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `komputer_akuntan`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `komputer_akuntan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `komputer_akuntan`;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `aksi` varchar(50) NOT NULL,
  `detail` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`user_id`),
  KEY `idx_log_aksi` (`aksi`),
  KEY `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `akun`
--

DROP TABLE IF EXISTS `akun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `akun` (
  `kode_akun` varchar(10) NOT NULL,
  `akun` varchar(50) NOT NULL,
  `aktiva_pasiva` enum('A','P') DEFAULT NULL,
  `kategori_neraca` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`kode_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `akun`
--

LOCK TABLES `akun` WRITE;
/*!40000 ALTER TABLE `akun` DISABLE KEYS */;
INSERT INTO `akun` VALUES ('101','Kas','A','Aktiva Lancar'),('102','Piutang Usaha','A','Aktiva Lancar'),('103','Supply','A','Aktiva Lancar'),('104','Peralatan','A','Aktiva Tetap'),('105','Beban Dibayar di Muka','A','Aktiva Lancar'),('106','Akumulasi Penyusutan Peralatan','A','Aktiva Tetap'),('201','Utang Usaha','P','Utang Lancar'),('202','Utang Gaji','P','Utang Lancar'),('251','Utang Bank','P','Utang Jangka Panjang'),('301','Modal','P','Ekuitas'),('401','Pendapatan Penjualan','P','Pendapatan'),('402','Pendapatan Jasa','P','Pendapatan'),('501','Beban Sewa','P','Beban'),('502','Beban Gaji','P','Beban'),('503','Beban Perlengkapan / Supply','P','Beban'),('504','Beban Penyusutan Peralatan','P','Beban');
/*!40000 ALTER TABLE `akun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aset_tetap`
--

DROP TABLE IF EXISTS `aset_tetap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aset_tetap` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_aset` varchar(255) NOT NULL,
  `harga_perolehan` decimal(15,2) NOT NULL,
  `nilai_residu` decimal(15,2) NOT NULL DEFAULT '0.00',
  `umur_ekonomis_bulan` int NOT NULL,
  `nilai_penyusutan_per_bulan` decimal(15,2) NOT NULL,
  `akun_aset` varchar(10) NOT NULL,
  `akun_akumulasi` varchar(10) NOT NULL,
  `akun_beban` varchar(10) NOT NULL,
  `last_adjusted_month` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aset_tetap`
--

LOCK TABLES `aset_tetap` WRITE;
/*!40000 ALTER TABLE `aset_tetap` DISABLE KEYS */;
/*!40000 ALTER TABLE `aset_tetap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_reaksi`
--

DROP TABLE IF EXISTS `detail_reaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_reaksi` (
  `id_detail_reaksi` int NOT NULL AUTO_INCREMENT,
  `id_reaksi` varchar(10) DEFAULT NULL,
  `kode_akun` varchar(10) DEFAULT NULL,
  `dk` varchar(10) NOT NULL,
  PRIMARY KEY (`id_detail_reaksi`),
  KEY `id_reaksi` (`id_reaksi`),
  KEY `kode_akun` (`kode_akun`),
  CONSTRAINT `detail_reaksi_ibfk_1` FOREIGN KEY (`id_reaksi`) REFERENCES `reaksi` (`id_reaksi`) ON DELETE CASCADE,
  CONSTRAINT `detail_reaksi_ibfk_2` FOREIGN KEY (`kode_akun`) REFERENCES `akun` (`kode_akun`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_reaksi`
--

LOCK TABLES `detail_reaksi` WRITE;
/*!40000 ALTER TABLE `detail_reaksi` DISABLE KEYS */;
INSERT INTO `detail_reaksi` VALUES (1,'R01','103','Debit'),(2,'R01','101','Kredit'),(3,'R02','101','Debit'),(4,'R02','402','Kredit'),(5,'R03','101','Debit'),(6,'R03','401','Kredit'),(7,'R04','502','Debit'),(8,'R04','101','Kredit'),(9,'R05','103','Debit'),(10,'R05','201','Kredit'),(15,'R04','201','Kredit'),(18,'R06','101','Debit'),(19,'R06','102','Debit'),(20,'R06','401','Kredit'),(21,'R07','101','Debit'),(22,'R07','102','Debit'),(23,'R07','402','Kredit'),(24,'R08','103','Debit'),(25,'R08','101','Kredit'),(26,'R08','201','Kredit'),(27,'R09','101','Debit'),(28,'R09','102','Kredit'),(29,'R10','201','Debit'),(30,'R10','101','Kredit');
/*!40000 ALTER TABLE `detail_reaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detil_transaksi`
--

DROP TABLE IF EXISTS `detil_transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detil_transaksi` (
  `kode_transaksi` varchar(10) NOT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `created_by` int DEFAULT NULL,
  `status_verifikasi` enum('pending','sesuai','koreksi') NOT NULL DEFAULT 'pending',
  `pengoreksi_id` varchar(100) DEFAULT NULL,
  `koreksi_dari_id` varchar(10) DEFAULT NULL,
  `jenis_jurnal` enum('umum','penyesuaian') NOT NULL DEFAULT 'umum',
  `sub_jenis` varchar(50) DEFAULT NULL,
  `is_reversing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Apakah jurnal ini perlu dibalik di awal periode berikutnya?',
  `reversed_at` datetime DEFAULT NULL COMMENT 'Waktu eksekusi jurnal pembalik, NULL jika belum dibalik',
  `reversed_jurnal_kode` varchar(20) DEFAULT NULL COMMENT 'Kode jurnal pembalik hasil generate',
  PRIMARY KEY (`kode_transaksi`),
  KEY `fk_detil_transaksi_user` (`created_by`),
  KEY `idx_reversing_pending` (`is_reversing`,`reversed_at`),
  CONSTRAINT `fk_detil_transaksi_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detil_transaksi`
--

LOCK TABLES `detil_transaksi` WRITE;
/*!40000 ALTER TABLE `detil_transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `detil_transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manajemen_prepaid`
--

DROP TABLE IF EXISTS `manajemen_prepaid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manajemen_prepaid` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_prepaid` varchar(255) NOT NULL,
  `total_nilai` decimal(15,2) NOT NULL,
  `lama_bulan` int NOT NULL,
  `nilai_per_bulan` decimal(15,2) NOT NULL,
  `akun_prepaid` varchar(10) NOT NULL,
  `akun_beban` varchar(10) NOT NULL,
  `last_adjusted_month` varchar(7) DEFAULT NULL,
  `bulan_terpakai` int DEFAULT '0',
  `tanggal_mulai` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manajemen_prepaid`
--

LOCK TABLES `manajemen_prepaid` WRITE;
/*!40000 ALTER TABLE `manajemen_prepaid` DISABLE KEYS */;
/*!40000 ALTER TABLE `manajemen_prepaid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `nama_perusahaan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES (1,'PT. BAHAGIA');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periode_akuntansi`
--

DROP TABLE IF EXISTS `periode_akuntansi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periode_akuntansi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_periode` varchar(50) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` enum('open','closed','reopened') NOT NULL DEFAULT 'open',
  `tanggal_closed` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `laba_periode` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_periode` (`tanggal_mulai`,`tanggal_selesai`),
  KEY `fk_closed_by` (`closed_by`),
  CONSTRAINT `fk_periode_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periode_akuntansi`
--

LOCK TABLES `periode_akuntansi` WRITE;
/*!40000 ALTER TABLE `periode_akuntansi` DISABLE KEYS */;
INSERT INTO `periode_akuntansi` VALUES (1,'Januari 2026','2026-01-01','2026-01-31','open',NULL,NULL,0.00),(2,'Februari 2026','2026-02-01','2026-02-28','open',NULL,NULL,0.00),(3,'Maret 2026','2026-03-01','2026-03-31','open',NULL,NULL,0.00),(4,'April 2026','2026-04-01','2026-04-30','open',NULL,NULL,0.00),(5,'Mei 2026','2026-05-01','2026-05-31','open',NULL,NULL,0.00),(6,'Juni 2026','2026-06-01','2026-06-30','open',NULL,NULL,0.00),(7,'Juli 2026','2026-07-01','2026-07-31','open',NULL,NULL,0.00),(8,'Agustus 2026','2026-08-01','2026-08-31','open',NULL,NULL,0.00),(9,'September 2026','2026-09-01','2026-09-30','open',NULL,NULL,0.00),(10,'Oktober 2026','2026-10-01','2026-10-31','open',NULL,NULL,0.00),(11,'November 2026','2026-11-01','2026-11-30','open',NULL,NULL,0.00),(12,'Desember 2026','2026-12-01','2026-12-31','open',NULL,NULL,0.00),(13,'Januari 2025','2025-01-01','2025-01-31','open',NULL,NULL,0.00),(14,'Februari 2025','2025-02-01','2025-02-28','open',NULL,NULL,0.00),(15,'Maret 2025','2025-03-01','2025-03-31','open',NULL,NULL,0.00),(16,'April 2025','2025-04-01','2025-04-30','open',NULL,NULL,0.00),(17,'Mei 2025','2025-05-01','2025-05-31','open',NULL,NULL,0.00),(18,'Juni 2025','2025-06-01','2025-06-30','open',NULL,NULL,0.00),(19,'Juli 2025','2025-07-01','2025-07-31','open',NULL,NULL,0.00),(20,'Agustus 2025','2025-08-01','2025-08-31','open',NULL,NULL,0.00),(21,'September 2025','2025-09-01','2025-09-30','open',NULL,NULL,0.00),(22,'Oktober 2025','2025-10-01','2025-10-31','open',NULL,NULL,0.00),(23,'November 2025','2025-11-01','2025-11-30','open',NULL,NULL,0.00),(24,'Desember 2025','2025-12-01','2025-12-31','open',NULL,NULL,0.00),(25,'Januari 2027','2027-01-01','2027-01-31','open',NULL,NULL,0.00),(26,'Februari 2027','2027-02-01','2027-02-28','open',NULL,NULL,0.00),(27,'Maret 2027','2027-03-01','2027-03-31','open',NULL,NULL,0.00),(28,'April 2027','2027-04-01','2027-04-30','open',NULL,NULL,0.00),(29,'Mei 2027','2027-05-01','2027-05-31','open',NULL,NULL,0.00),(30,'Juni 2027','2027-06-01','2027-06-30','open',NULL,NULL,0.00),(31,'Juli 2027','2027-07-01','2027-07-31','open',NULL,NULL,0.00),(32,'Agustus 2027','2027-08-01','2027-08-31','open',NULL,NULL,0.00),(33,'September 2027','2027-09-01','2027-09-30','open',NULL,NULL,0.00),(34,'Oktober 2027','2027-10-01','2027-10-31','open',NULL,NULL,0.00),(35,'November 2027','2027-11-01','2027-11-30','open',NULL,NULL,0.00),(36,'Desember 2027','2027-12-01','2027-12-31','open',NULL,NULL,0.00);
/*!40000 ALTER TABLE `periode_akuntansi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reaksi`
--

DROP TABLE IF EXISTS `reaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reaksi` (
  `id_reaksi` varchar(10) NOT NULL,
  `nama_reaksi` varchar(100) NOT NULL,
  PRIMARY KEY (`id_reaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reaksi`
--

LOCK TABLES `reaksi` WRITE;
/*!40000 ALTER TABLE `reaksi` DISABLE KEYS */;
INSERT INTO `reaksi` VALUES ('R01','Pembelian Stok Tunai'),('R02','Pendapatan Jasa Tunai'),('R03','Penjualan Unit Tunai'),('R04','Pembayaran Gaji Pegawai'),('R05','Pembelian Stok Kredit'),('R06','Penjualan Unit dengan DP'),('R07','Pendapatan Jasa dengan DP'),('R08','Pembelian Stok dengan DP'),('R09','Penerimaan Pelunasan Piutang'),('R10','Pembayaran Pelunasan Utang');
/*!40000 ALTER TABLE `reaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saldo_akun_per_periode`
--

DROP TABLE IF EXISTS `saldo_akun_per_periode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saldo_akun_per_periode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `periode_id` int NOT NULL,
  `akun_id` varchar(10) NOT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `saldo_debet` decimal(15,2) DEFAULT '0.00',
  `saldo_kredit` decimal(15,2) DEFAULT '0.00',
  `versi` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `revisi_dari_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_snapshot_periode` (`periode_id`),
  KEY `fk_snapshot_akun` (`akun_id`),
  KEY `idx_snapshot_active` (`periode_id`,`is_active`),
  KEY `fk_snapshot_revisi` (`revisi_dari_id`),
  CONSTRAINT `fk_snapshot_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`kode_akun`) ON DELETE CASCADE,
  CONSTRAINT `fk_snapshot_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode_akuntansi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_snapshot_revisi` FOREIGN KEY (`revisi_dari_id`) REFERENCES `saldo_akun_per_periode` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saldo_akun_per_periode`
--

LOCK TABLES `saldo_akun_per_periode` WRITE;
/*!40000 ALTER TABLE `saldo_akun_per_periode` DISABLE KEYS */;
/*!40000 ALTER TABLE `saldo_akun_per_periode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id_jurnal` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(10) DEFAULT NULL,
  `kode_akun` varchar(10) DEFAULT NULL,
  `dk` varchar(10) DEFAULT NULL,
  `nilai` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id_jurnal`),
  KEY `kode_transaksi` (`kode_transaksi`),
  KEY `kode_akun` (`kode_akun`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`kode_transaksi`) REFERENCES `detil_transaksi` (`kode_transaksi`) ON DELETE CASCADE,
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`kode_akun`) REFERENCES `akun` (`kode_akun`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('cashier','accountant','admin') NOT NULL DEFAULT 'cashier',
  `firebase_uid` varchar(128) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `idx_firebase_uid` (`firebase_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES 
(1,'admin','$2y$10$b8oVYllu3p46A4T09pwj5uKRuidLnyc6135ZxDVAAEAnheSu2b/RG','Administrator','admin',NULL,'2026-05-19 09:51:48','2026-06-30 20:34:21'),
(2,'kasir1','$2y$10$DOmXS1TnBasixqPCAuucrefP3dOI5D8IJyHJAKo4zD7akQ/PF/fiO','Kasir Pertama','cashier',NULL,'2026-05-19 09:56:29','2026-06-30 22:11:04'),
(7,'akuntan1','$2y$10$Xm.YoarBD78Lvt24AORh/eSz8.KofHScABbVNFz8C7mHjQfLvaw1q','akuntan1','accountant',NULL,'2026-09-02 09:18:18','2026-09-02 09:18:18');
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
