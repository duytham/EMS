-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: localhost    Database: edms
-- ------------------------------------------------------
-- Server version	8.0.39

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
-- Table structure for table `checkinout`
--

DROP TABLE IF EXISTS `checkinout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkinout` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `ActionType` enum('checkin','checkout') NOT NULL,
  `CheckInTime` datetime DEFAULT NULL,
  `LogDate` date NOT NULL,
  `CheckOutTime` datetime DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT '0',
  `status` varchar(10) DEFAULT 'Valid',
  `reason` text,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_user_logdate` (`UserID`,`LogDate`),
  CONSTRAINT `checkinout_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkinout`
--

LOCK TABLES `checkinout` WRITE;
/*!40000 ALTER TABLE `checkinout` DISABLE KEYS */;
INSERT INTO `checkinout` VALUES (45,22,'checkout','2024-11-07 22:24:39','2024-11-07',NULL,0,'Valid','về đu idol'),(46,67,'checkin','2024-11-07 22:25:16','2024-11-07',NULL,0,'Invalid','săn vé anh trai say bye '),(47,68,'checkin','2024-11-08 00:49:37','2024-11-08','2024-11-08 00:49:39',0,'Invalid','Em muốn về sớm để xem Anh trai say bye'),(48,22,'checkin','2024-11-08 03:23:51','2024-11-08','2024-11-08 03:25:21',0,'Vaild','hehe'),(49,67,'checkin','2024-11-08 11:13:18','2024-11-08','2024-11-08 11:15:34',0,'Pending','hehe'),(50,22,'checkin','2024-11-12 10:31:48','2024-11-12','2024-11-12 11:54:41',0,'Valid','hehe'),(51,67,'checkin','2024-11-12 14:58:14','2024-11-12',NULL,0,'Valid','hihi'),(54,22,'checkin','2024-11-16 23:52:53','2024-11-16',NULL,0,'Valid','Personal problems: Having an urgent matter to attend to (e.g. family issues).'),(56,67,'checkin','2024-11-17 00:54:51','2024-11-17','2024-11-17 00:54:55',0,'Invalid','Bad weather: Due to unfavorable weather (rainstorms, snow, etc.).'),(57,22,'checkin','2024-11-18 14:29:08','2024-11-18',NULL,0,'Invalid',NULL),(58,22,'checkin','2024-11-19 22:24:20','2024-11-19',NULL,0,'Valid','Heavy traffic: Due to traffic jams or poor traffic conditions.'),(59,22,'checkin','2024-11-21 00:19:18','2024-11-21','2024-11-21 10:58:48',0,'Invalid','Bad weather: Due to unfavorable weather (rainstorms, snow, etc.).'),(60,22,'checkin','2024-11-22 02:33:56','2024-11-22',NULL,0,'Valid',NULL),(61,22,'checkin','2024-11-24 02:29:18','2024-11-24','2024-11-24 02:29:20',0,'Invalid','Heavy traffic: Due to traffic jams or poor traffic conditions.');
/*!40000 ALTER TABLE `checkinout` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-11-24 14:12:28
