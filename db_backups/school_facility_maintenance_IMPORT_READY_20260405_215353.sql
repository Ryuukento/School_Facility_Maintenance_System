-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: school_facility_maintenance
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_action_index` (`user_id`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'LOGIN','user',1,'User logged in','::1','2026-03-27 14:55:27','2026-03-27 14:55:27'),(2,1,'LOGIN','user',1,'User logged in','::1','2026-03-29 10:06:12','2026-03-29 10:06:12'),(3,3,'REGISTER','user',3,'Self-registered account (pending approval)','::1','2026-03-29 10:07:49','2026-03-29 10:07:49'),(4,1,'LOGIN','user',1,'User logged in','::1','2026-03-29 10:08:26','2026-03-29 10:08:26'),(5,1,'APPROVE_USER','user',3,'Approved user #3 and assigned role maintenance_staff','::1','2026-03-29 10:29:12','2026-03-29 10:29:12'),(6,1,'INACTIVATE_USER','user',3,'Set user #3 to inactive','::1','2026-03-29 10:29:16','2026-03-29 10:29:16'),(7,1,'ACTIVATE_USER','user',3,'Set user #3 to active','::1','2026-03-29 10:29:19','2026-03-29 10:29:19'),(8,3,'LOGIN','user',3,'User logged in','::1','2026-03-29 10:29:50','2026-03-29 10:29:50'),(9,3,'CREATE_REPORT','report',1,NULL,NULL,'2026-03-29 10:50:54',NULL),(10,1,'LOGIN','user',1,'User logged in','::1','2026-03-29 11:01:02','2026-03-29 11:01:02'),(11,1,'INACTIVATE_USER','user',3,'Set user #3 to inactive','::1','2026-03-29 11:01:16','2026-03-29 11:01:16'),(12,2,'LOGIN','user',2,'User logged in','::1','2026-03-29 11:01:54','2026-03-29 11:01:54'),(13,1,'LOGIN','user',1,'User logged in','::1','2026-03-29 11:08:18','2026-03-29 11:08:18'),(14,1,'LOGIN','user',1,'User logged in','::1','2026-03-31 11:12:47','2026-03-31 11:12:47'),(15,1,'LOGIN','user',1,'User logged in','::1','2026-03-31 13:36:36','2026-03-31 13:36:36'),(17,1,'LOGIN','user',1,'User logged in','::1','2026-03-31 13:41:27','2026-03-31 13:41:27'),(18,1,'REJECT_USER','user',4,'Rejected pending user #4 and deleted account','::1','2026-03-31 13:41:35','2026-03-31 13:41:35'),(20,1,'LOGIN','user',1,'User logged in','::1','2026-03-31 13:45:12','2026-03-31 13:45:12'),(21,1,'REJECT_USER','user',5,'Rejected pending user #5 and deleted account','::1','2026-03-31 13:46:36','2026-03-31 13:46:36'),(23,1,'LOGIN','user',1,'User logged in','::1','2026-03-31 13:50:34','2026-03-31 13:50:34'),(24,1,'REJECT_USER','user',6,'Rejected pending user #6 and deleted account','::1','2026-03-31 13:50:45','2026-03-31 13:50:45'),(26,1,'LOGIN','user',1,'User logged in','::1','2026-04-01 06:52:28','2026-04-01 06:52:28'),(27,1,'LOGIN','user',1,'User logged in','::1','2026-04-02 13:26:04','2026-04-02 13:26:04'),(28,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(29,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(30,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(31,1,'ACTIVATE_USER','user',3,'Set user #3 to active','::1',NULL,NULL),(32,1,'REJECT_USER','user',7,'Rejected pending user #7 and deleted account','::1',NULL,NULL),(33,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(34,3,'CREATE_REPORT','report',2,NULL,NULL,'2026-04-03 05:17:08',NULL),(35,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(36,3,'UPDATE_REPORT','report',2,'{\"title\":\"Laravel Convert Test\",\"location\":\"Lourdes V 4 floor room 102\",\"priority\":\"urgent\",\"status\":\"submitted\",\"description\":\"adasdeas\"}',NULL,'2026-04-03 05:24:55',NULL),(37,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(38,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(39,3,'CREATE_REPORT','report',3,NULL,NULL,'2026-04-03 05:39:30',NULL),(40,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(41,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(42,3,'DELETE_REPORT','report',3,'\"Deleted report #3\"',NULL,'2026-04-03 05:50:22',NULL),(43,3,'CREATE_REPORT','report',4,NULL,NULL,'2026-04-03 05:50:54',NULL),(44,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(45,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(46,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(47,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(48,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(49,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(50,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(51,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(52,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(53,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(54,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(55,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(56,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(57,1,'INACTIVATE_USER','user',3,'Set user #3 to inactive','::1',NULL,NULL),(58,1,'ACTIVATE_USER','user',3,'Set user #3 to active','::1',NULL,NULL),(59,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(60,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(61,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(62,1,'INACTIVATE_USER','user',3,'Set user #3 to inactive','::1',NULL,NULL),(63,1,'ACTIVATE_USER','user',3,'Set user #3 to active','::1',NULL,NULL),(65,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(66,1,'REJECT_USER','user',8,'Rejected pending user #8 and deleted account','::1',NULL,NULL),(67,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(68,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(69,1,'LOGOUT','user',1,'User logged out','::1',NULL,NULL),(70,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(71,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(72,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(73,1,'DELETE_REPORT','report',4,'\"Deleted report #4\"',NULL,'2026-04-04 13:08:11',NULL),(74,1,'DELETE_REPORT','report',2,'\"Deleted report #2\"',NULL,'2026-04-04 13:08:16',NULL),(75,1,'DELETE_REPORT','report',1,'\"Deleted report #1\"',NULL,'2026-04-04 13:08:43',NULL),(76,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(77,3,'CREATE_REPORT','report',5,NULL,NULL,'2026-04-04 13:10:31',NULL),(78,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(79,1,'RESERVE_INVENTORY','inventory_item',5,'{\"report_id\":5,\"room_id\":1,\"quantity\":1,\"allocation_id\":1}',NULL,'2026-04-04 13:11:41',NULL),(80,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(81,1,'CREATE_RESTOCK_REQUEST','inventory_item',NULL,'{\"restock_id\":1,\"item_name\":\"Whiteboard Marker Set\",\"requested_qty\":1,\"priority\":\"high\",\"source_report_id\":5}',NULL,'2026-04-05 04:55:40',NULL),(82,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(83,2,'LOGOUT','user',2,'User logged out','::1',NULL,NULL),(84,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(85,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(87,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(88,1,'UPDATE_REPORT','report',5,'{\"status\":\"assigned\",\"assigned_to\":\"3\"}',NULL,'2026-04-05 05:36:36',NULL),(89,1,'REJECT_USER','user',9,'Rejected pending user #9 and deleted account','::1',NULL,NULL),(90,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(91,3,'UPDATE_REPORT','report',5,'{\"title\":\"Testing\",\"location\":\"103\",\"priority\":\"critical\",\"status\":\"submitted\",\"description\":\"asdasd\"}',NULL,'2026-04-05 05:41:09',NULL),(92,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(93,2,'UPDATE_REPORT','report',5,'{\"status\":\"completed\",\"assigned_to\":null}',NULL,'2026-04-05 05:44:01',NULL),(94,2,'UPDATE_REPORT','report',5,'{\"status\":\"in_progress\",\"assigned_to\":null}',NULL,'2026-04-05 05:44:06',NULL),(95,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(96,3,'CREATE_REPORT','report',6,NULL,NULL,'2026-04-05 05:46:10',NULL),(97,3,'LOGOUT','user',3,'User logged out','::1',NULL,NULL),(98,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(99,1,'UPDATE_REPORT','report',6,'{\"status\":\"assigned\",\"assigned_to\":\"2\"}',NULL,'2026-04-05 05:47:01',NULL),(100,2,'LOGIN','user',2,'User logged in','::1',NULL,NULL),(101,2,'UPDATE_REPORT','report',6,'{\"status\":\"assigned\",\"assigned_to\":\"3\"}',NULL,'2026-04-05 05:50:56',NULL),(102,2,'UPDATE_REPORT','report',6,'{\"status\":\"in_progress\",\"assigned_to\":null}',NULL,'2026-04-05 05:51:07',NULL),(103,2,'UPDATE_REPORT','report',6,'{\"status\":\"completed\",\"assigned_to\":null}',NULL,'2026-04-05 05:51:26',NULL),(104,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(105,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(106,3,'CREATE_REPORT','report',7,NULL,NULL,'2026-04-05 05:53:01',NULL),(107,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(108,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(109,10,'REGISTER','user',10,'Self-registered account (pending approval)','::1',NULL,NULL),(110,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(111,1,'APPROVE_USER','user',10,'Approved user #10 and assigned role maintenance_admin','::1',NULL,NULL),(112,10,'LOGIN','user',10,'User logged in','::1',NULL,NULL),(113,10,'LOGOUT','user',10,'User logged out','::1',NULL,NULL),(114,3,'LOGIN','user',3,'User logged in','::1',NULL,NULL),(115,1,'LOGIN','user',1,'User logged in','::1',NULL,NULL),(116,1,'LOGIN','user',1,'User logged in','127.0.0.1',NULL,NULL),(117,1,'LOGIN','user',1,'User logged in','127.0.0.1',NULL,NULL),(118,1,'INACTIVATE_USER','user',2,'Set user #2 to inactive','127.0.0.1',NULL,NULL),(119,1,'ACTIVATE_USER','user',2,'Set user #2 to active','127.0.0.1',NULL,NULL),(120,1,'LOGIN','user',1,'User logged in','127.0.0.1',NULL,NULL);
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buildings`
--

DROP TABLE IF EXISTS `buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buildings_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buildings`
--

LOCK TABLES `buildings` WRITE;
/*!40000 ALTER TABLE `buildings` DISABLE KEYS */;
INSERT INTO `buildings` VALUES (9,'Lourdes Building VIII','','2026-04-03 08:09:42','2026-04-03 08:09:42'),(10,'Lourdes Building VII','','2026-04-03 08:09:51','2026-04-03 08:09:51'),(11,'Lourdes Building VI','','2026-04-03 08:09:59','2026-04-03 08:09:59'),(12,'Lourdes Building V','','2026-04-03 08:10:06','2026-04-03 08:10:06'),(13,'Lourdes Building IV','','2026-04-03 08:10:32','2026-04-03 08:10:32'),(14,'Lourdes Building III','','2026-04-03 08:10:42','2026-04-03 08:10:42'),(15,'Lourdes Building II','','2026-04-03 08:10:48','2026-04-03 08:10:48'),(16,'Lourdes Building I','','2026-04-03 08:10:56','2026-04-03 08:11:02');
/*!40000 ALTER TABLE `buildings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `department_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `departments_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `floors`
--

DROP TABLE IF EXISTS `floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `floors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `floors_building_id_name_unique` (`building_id`,`name`),
  CONSTRAINT `floors_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `floors`
--

LOCK TABLES `floors` WRITE;
/*!40000 ALTER TABLE `floors` DISABLE KEYS */;
INSERT INTO `floors` VALUES (1,16,'1st Floor',NULL,'2026-04-03 08:11:35','2026-04-03 08:11:35'),(2,16,'2nd',NULL,'2026-04-03 08:11:41','2026-04-03 08:11:41'),(3,16,'3rd floor',NULL,'2026-04-03 08:11:44','2026-04-03 08:11:44'),(4,16,'4rd Floor',NULL,'2026-04-03 08:12:03','2026-04-03 08:12:03');
/*!40000 ALTER TABLE `floors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `report_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `transaction_type` enum('reserve','release','deploy','return','adjustment','dispose') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_note` text DEFAULT NULL,
  `performed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_item_id_transaction_type_index` (`item_id`,`transaction_type`),
  KEY `inventory_transactions_report_id_index` (`report_id`),
  KEY `inventory_transactions_room_id_index` (`room_id`),
  KEY `inventory_transactions_performed_by_foreign` (`performed_by`),
  CONSTRAINT `inventory_transactions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
INSERT INTO `inventory_transactions` VALUES (1,5,5,1,'reserve',1,'Reserved for report #5',1,'2026-04-04 13:11:41',NULL);
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('available','damaged','low_stock','out_of_stock','maintenance') NOT NULL DEFAULT 'available',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 5,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `items_room_id_status_index` (`room_id`,`status`),
  CONSTRAINT `items_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,2,'Projector Epson X51','available',6,0,5,'Portable projector for classroom presentations','2026-04-04 02:02:35','2026-04-04 02:02:35'),(2,1,'Whiteboard Marker Set','low_stock',12,0,5,'Assorted marker colors','2026-04-04 02:02:35','2026-04-04 02:02:35'),(3,1,'Extension Cord Heavy Duty','available',18,0,5,'5-meter grounded extension cord','2026-04-04 02:02:35','2026-04-04 02:02:35'),(4,2,'LCD Monitor 24 inch','maintenance',3,0,5,'Needs HDMI port repair','2026-04-04 02:02:35','2026-04-04 02:02:35'),(5,1,'Office Chair','damaged',4,1,5,'Broken wheel assembly','2026-04-04 02:02:35','2026-04-04 02:02:35'),(6,1,'Printer Ink Cartridge HP 680','out_of_stock',0,0,5,'Black ink for faculty office printer','2026-04-04 02:02:35','2026-04-04 02:02:35');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_reports`
--

DROP TABLE IF EXISTS `maintenance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_reports` (
  `report_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent','critical') NOT NULL DEFAULT 'medium',
  `status` enum('submitted','in_progress','completed','closed','cancelled') NOT NULL DEFAULT 'submitted',
  `created_by` int(10) unsigned NOT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `maintenance_reports_status_priority_index` (`status`,`priority`),
  KEY `maintenance_reports_created_by_assigned_to_index` (`created_by`,`assigned_to`),
  KEY `maintenance_reports_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `maintenance_reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_reports`
--

LOCK TABLES `maintenance_reports` WRITE;
/*!40000 ALTER TABLE `maintenance_reports` DISABLE KEYS */;
INSERT INTO `maintenance_reports` VALUES (5,'Testing','asdasd','103','critical','in_progress',3,NULL,NULL,NULL,NULL,'2026-04-04 13:10:31','2026-04-05 05:44:06'),(6,'Broken Chair','2 Chairs has been broke','Building 1 room 103','low','completed',3,NULL,NULL,NULL,NULL,'2026-04-05 05:46:10','2026-04-05 05:51:26'),(7,'Alert this is testing report only','testing report only','Lourdes Building 4 Room 2-6','medium','submitted',3,NULL,NULL,NULL,NULL,'2026-04-05 05:53:01',NULL);
/*!40000 ALTER TABLE `maintenance_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_27_000100_create_departments_table',1),(5,'2026_03_27_000200_create_activity_logs_table',1),(6,'2026_03_27_000300_create_facility_tables',1),(7,'2026_03_27_000400_create_maintenance_reports_table',1),(8,'2026_03_27_000500_create_notifications_table',1),(9,'2026_04_04_000600_create_inventory_workflow_tables',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (3,2,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Test Email/Dashboard Notification',1,'2026-04-03 05:50:54',NULL),(4,1,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Testing (Report #5)',1,'2026-04-04 13:10:31',NULL),(5,2,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Testing (Report #5)',0,'2026-04-04 13:10:31',NULL),(6,3,'Report Assigned to You','Ryan Mondido assigned report #5 (Testing) to you.',1,'2026-04-05 05:36:36',NULL),(7,1,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Broken Chair (Report #6)',0,'2026-04-05 05:46:10',NULL),(8,2,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Broken Chair (Report #6)',0,'2026-04-05 05:46:10',NULL),(9,2,'Report Assigned to You','Ryan Mondido assigned report #6 (Broken Chair) to you.',1,'2026-04-05 05:47:01',NULL),(10,3,'Report Assigned to You','Maintenance Admin assigned report #6 (Broken Chair) to you.',0,'2026-04-05 05:50:56',NULL),(11,1,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Alert this is testing report only (Report #7)',0,'2026-04-05 05:53:01',NULL),(12,2,'New Maintenance Report Submitted','Mark Joseph Barreto submitted a new report: Alert this is testing report only (Report #7)',0,'2026-04-05 05:53:01',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_inventory_allocations`
--

DROP TABLE IF EXISTS `report_inventory_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_inventory_allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `reserved_qty` int(11) NOT NULL DEFAULT 0,
  `deployed_qty` int(11) NOT NULL DEFAULT 0,
  `status` enum('reserved','deployed','cancelled') NOT NULL DEFAULT 'reserved',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_inventory_allocations_report_id_status_index` (`report_id`,`status`),
  KEY `report_inventory_allocations_item_id_room_id_index` (`item_id`,`room_id`),
  KEY `report_inventory_allocations_room_id_foreign` (`room_id`),
  KEY `report_inventory_allocations_created_by_foreign` (`created_by`),
  CONSTRAINT `report_inventory_allocations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `report_inventory_allocations_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_inventory_allocations_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE CASCADE,
  CONSTRAINT `report_inventory_allocations_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_inventory_allocations`
--

LOCK TABLES `report_inventory_allocations` WRITE;
/*!40000 ALTER TABLE `report_inventory_allocations` DISABLE KEYS */;
INSERT INTO `report_inventory_allocations` VALUES (1,5,5,1,1,0,'reserved',1,'2026-04-04 13:11:41','2026-04-04 13:11:41');
/*!40000 ALTER TABLE `report_inventory_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restock_requests`
--

DROP TABLE IF EXISTS `restock_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restock_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `requested_qty` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `source_report_id` int(10) unsigned DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','approved','ordered','received','cancelled') NOT NULL DEFAULT 'open',
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `restock_requests_status_priority_index` (`status`,`priority`),
  KEY `restock_requests_source_report_id_foreign` (`source_report_id`),
  KEY `restock_requests_requested_by_foreign` (`requested_by`),
  KEY `restock_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `restock_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `restock_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `restock_requests_source_report_id_foreign` FOREIGN KEY (`source_report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restock_requests`
--

LOCK TABLES `restock_requests` WRITE;
/*!40000 ALTER TABLE `restock_requests` DISABLE KEYS */;
INSERT INTO `restock_requests` VALUES (1,'Whiteboard Marker Set',1,'Requested from report #5',5,'high','open',1,NULL,'2026-04-05 04:55:40','2026-04-05 04:55:40');
/*!40000 ALTER TABLE `restock_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int(10) unsigned NOT NULL,
  `floor_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_building_id_name_unique` (`building_id`,`name`),
  UNIQUE KEY `rooms_floor_id_name_unique` (`floor_id`,`name`),
  CONSTRAINT `rooms_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rooms_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,16,1,'Storage Room A',50,'2026-04-04 02:02:35','2026-04-04 02:02:35'),(2,16,1,'Science Lab 101',50,'2026-04-04 02:02:35','2026-04-04 02:02:35'),(3,16,2,'HAHAHA ROOMS',50000,'2026-04-05 13:51:33','2026-04-05 13:51:33');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('DovRRSKo7UX3aQdohaGiy2P5bOWOVAwz7EFZSkOM',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNWNaZkNiUFV3Y2l0M0xvdFBoSHRTUW8ybEtURUtpbVlTOUg4Y3VabyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvZGFzaGJvYXJkL3N0YXRzIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1775397106),('ETx0EgrQmQJElMIZKTybBgj32iZFezYQ8TnNjI5C',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3F5b1dXVFNscDRuZTFNMTZNUEZ1dmZ2ZGRtV2RqZDNTSHFlMWdmaiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6OTA6Imh0dHA6Ly9sb2NhbGhvc3QvU2Nob29sX0ZhY2lsaXR5X01haW50ZW5hbmNlX1N5c3RlbS9sYXJhdmVsX2FwcC9wdWJsaWMvYXBpL2Rhc2hib2FyZC9zdGF0cyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1775374840),('LCPNbJfcu7Opzw2DoDfXCzgLAsq0MYLEwlkKgZeb',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22000.2538','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSDhzM3lhcFRsVGRXYnM0ODRjcFpUTUtzekdyVnlDM1VGTVlRU3RoRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTAwOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvU2Nob29sX0ZhY2lsaXR5X01haW50ZW5hbmNlX1N5c3RlbS9sYXJhdmVsX2FwcC9wdWJsaWMvZnJvbnRlbmQvcGFnZXMvaW5kZXgucGhwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1775396908);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `department_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
  `avatar` varchar(500) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Ryan Mondido','Ryan27@gmail.com','$2y$12$G7PQ9WsUACWsw1ydHfFk4.QT.vnggj5Iu2wppPzFhjpEvp1ab8Zme','super_admin',NULL,'active','/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/uploads/avatars/avatar_1_1775288798.jpg',NULL,'2026-03-27 14:52:33','2026-04-04 11:17:05'),(2,'Maintenance Admin','Mariah22@gmail.com','$2y$12$aYNroBjs7cBH4kVRIKZAXeMSd2fPk9tFdl9ahL/Ic4Xcyfrjwa3bS','maintenance_admin',NULL,'active','/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/uploads/avatars/avatar_2_1775365054.jpg',NULL,'2026-03-27 14:52:33','2026-04-05 13:38:56'),(3,'Mark Joseph Barreto','Mark11@gmail.com','$2y$12$t8Tjy31KkVZBFLBAp6IIoerbiH9gT1c0MzMRJpdclT21kXPcxHgzy','maintenance_staff',NULL,'active','/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/uploads/avatars/avatar_3_1775365080.jpg',NULL,'2026-03-29 10:07:49','2026-04-05 04:58:00'),(10,'Jerick Lopez','jerick11@gmail.com','$2y$12$DHV/FaTcX8MOmQxRqGgbW..aWwWlaTC6h.flJPwkL3YNwyJNq/4wi','maintenance_admin',NULL,'active',NULL,NULL,'2026-04-05 06:05:19','2026-04-05 06:05:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'school_facility_maintenance'
--

--
-- Dumping routines for database 'school_facility_maintenance'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-05 21:53:54
