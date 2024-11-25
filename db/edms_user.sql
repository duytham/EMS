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
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `FullName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Position` varchar(100) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `DepartmentID` int DEFAULT NULL,
  `RoleID` int NOT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `salary_level_id` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Email_UNIQUE` (`Email`),
  KEY `RoleID` (`RoleID`),
  KEY `user_ibfk_1` (`DepartmentID`),
  KEY `salary_level_id` (`salary_level_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`RoleID`) REFERENCES `role` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_ibfk_3` FOREIGN KEY (`salary_level_id`) REFERENCES `salary_levels` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (9,'admin','admin@gmail.com','0123456789',NULL,'$2y$10$tyhNogqeU6W2R0GHuFKoN.N1IfXh2kaPCLrZs.fkoUMw2hA983sCO',NULL,1,'active',18),(10,'department','department@gmail.com','0123456789',NULL,'$2y$10$tyhNogqeU6W2R0GHuFKoN.N1IfXh2kaPCLrZs.fkoUMw2hA983sCO',1,3,'active',19),(18,'duy dudđggg123eeee','duynkhe163378@fpt.edu.vnf6333','0702081553',NULL,'$2y$10$jB4l1262m0UUh80UeoiWtePUt.H8K4QDIR0M8XznfeOaN8Sa2nik.',4,2,'inactive',27),(20,'HE163378 - Nguyễn Khánh Duy','123@gmail.com','0838492002',NULL,'$2y$10$QYBw6fjJQqdmdE1YWeA.7eG0.zFCmv8DEYQRNZRHoBrTEQ7PU58xu',1,2,'inactive',21),(21,'HE163378 - Nguyễn Khánh Duy','duytham026@gmail.com','0838492002',NULL,'$2y$10$OsUlHGQsZH0V0uKSbCQAwuS3a9IKzm0dgmk1wrDCHSP0UV6t9L.IS',1,2,'active',27),(22,'Nguyen Khanh Duy','user@gmail.com','0123456789',NULL,'$2y$10$cwIY5DF88Clo/WMV56DX1eqL2thZgD353FAsrctA4T851KUBfm4Pe',1,2,'active',20),(25,'demo','demo1@gmail.com','0702081553',NULL,'$2y$10$G56pbzCb9kkDZa8DUr6xbOC2wRJGEJdopCdhV5DZFVtEPl0tpMuO2',1,2,'inactive',19),(32,'demo','demo123@gmail.com','0702081553',NULL,'$2y$10$1hf8LoxHT5d.E51vLGYNnu8wQOk/gQ8LSAsoIgroPAw9HZ6OQewce',4,2,'inactive',20),(33,'QueTD2','quetd2@fe.edu.vn','0702081553',NULL,'$2y$10$XNQVWqCaYqOOX3MkBqdWUu5H8N5RbA31/iZqTTUHVwGEHpnyYEmNi',3,2,'inactive',21),(34,'QueTD2','quetd2@fe.edu.vn2','0702081553',NULL,'$2y$10$PeLwf6V7bK0RnlpAcVN6D.4Y8LtccIXqh5/S2LPmn4.k9OAmG3Jsa',3,2,'inactive',27),(35,'teest','tets@gmail.com','123111111',NULL,'$2y$10$jWcLVCMvKPQv0lpn6kw5QO0kqYXQtr7rRdqGOK8Kg3p.A8oejH9cG',4,2,'active',18),(50,'Nguyễn Văn A','demo11@gmail.com','987654321',NULL,'$2y$10$ixQowv7NT6G.hxdvwFoKAe86fbjafE8j8ndl7Fxzufye8D4nwOidy',NULL,2,'active',19),(51,'Nguyễn Văn A','demo12@gmail.com','987654321',NULL,'$2y$10$1GIVz73BPksJA./WmxhV8OBezgDAz/F5imhbCAth.iefZYeGDzzpW',4,2,'active',20),(52,'abcd','demo13@gmail.com','123456789',NULL,'$2y$10$7TFSqpSEnRNDoR8tb3mNNeegP0MWuopkTjTJk5qP0hoLj2ol8B/jW',NULL,2,'active',21),(58,'Demo 14','demo14@gmail.com',NULL,NULL,'$2y$10$G0GSv8.wIkK6ytAroOZmBuDIGF3GSwrqnzK/7KW2gaZBa9UUhtODK',NULL,2,'active',27),(59,'Demo 15','demo15@gmail.com',NULL,NULL,'$2y$10$udznaRuisRFWnIN/X5K81Oix3Da2GuDi5ye6QhwfUburGozJI./uq',NULL,2,'active',NULL),(60,'Demo 16','demo16@gmail.com',NULL,NULL,'$2y$10$SMBMjfX5FXeIv8Cz/vawguxX0aT2jTTeKHW5W.vyS.VuvRzSWLXvi',NULL,2,'active',NULL),(61,'Demo 17','demo17@gmail.com',NULL,NULL,'$2y$10$Z9QHbGeGrqs5cVw3s8ojjutNkbsRfXTcQFODeJHUETObGsmvS4ByC',NULL,2,'active',NULL),(62,'Demo 18','demo18@gmail.com','123456789',NULL,'$2y$10$XSYpd5c2IQQWDiuaemdn2OoI2Ijl/MQm6ZyFLwTyiQTc7vW.pnUrO',2,2,'inactive',NULL),(63,'teest','tets1@gmail.com','0123456789',NULL,'$2y$10$bClImvxs.a7vRTTyxOtv9OE2cgf14y3vKt9cWMa0kpaa3Xto2r/zS',1,2,'active',NULL),(64,'Demo 18','demo19@gmail.com','1233333333',NULL,'$2y$10$oCYvcfSpkm1XzwhhqvWnfe6ooJG4aUb7EGH0grUpsBUZ8W9nwPph2',3,2,'active',NULL),(66,'Demo 18','demo20@gmail.com','1233333333',NULL,'$2y$10$1XglY0mFcTGOR9S4poZ/5Oku3tF69dWYIEI4by.inefCVNh0IA9Om',3,2,'active',NULL),(67,'Nguyen Duy Khanh','duynkhe163378@fpt.edu.vn','0702081553',NULL,'$2y$10$ifIAf7HBVG0IxCUs34Cbveyx32YC89lr8/zmWIMgysBRmItu7LDIe',1,2,'active',NULL),(68,'Demo 70','demo70@gmail.com','123456789',NULL,'$2y$10$rTPSjqbulRarKyvD1dxyFuddaUUtN7lP1BJiwCsyja5HwDSPdstRm',3,2,'active',NULL),(69,'Demo 70','demo71@gmail.com','123456789',NULL,'$2y$10$BNIiMN1g/9LhiFGWtxtD5.IJP8ixYOoxfuIuduCYb0C8tFT1jtdvq',3,2,'active',NULL),(70,'Demo 23','demo23@gmail.com','613139565',NULL,'$2y$10$5u9ZTErdmoAscpp2H90zEeCoVrglYMPyfvpy.qndvKbX19QJyrSWW',3,2,'active',NULL),(71,'Demo 24','demo24@gmail.com','857980953',NULL,'$2y$10$DgIXhk1Vy5JjUjGnRzgQ4OZYgByr9s85bGHZrN1KhRhiPGX7FaG.u',4,2,'active',NULL),(72,'Demo 25','demo25@gmail.com','1102822341',NULL,'$2y$10$Yw7koPr8GvFwOSZaasNzHOdDokNi2dz4UO3rgFEc3SAD5aIgocF6e',2,2,'active',NULL),(73,'Demo 26','demo26@gmail.com','1347663729',NULL,'$2y$10$CfmdDCh/hVYhPrK92flqIepLAoA2ePDxyOkBri3RqGQyauSWf6Elu',3,2,'active',NULL),(74,'Demo 27','demo27@gmail.com','1592505117',NULL,'$2y$10$3K..yLsuTsQIZo69KODCeOblich5/4BmG48TBtHf2KIl7zIgj5pnS',4,2,'active',NULL),(75,'Demo 28','demo28@gmail.com','1837346505',NULL,'$2y$10$zDkwOGeZcIjAoJn8A4plDubE8px1pB2iOcnt3kEAJKvohNwdSkXSK',3,2,'active',NULL),(76,'Demo 29','demo29@gmail.com','2082187893',NULL,'$2y$10$Si/6lm6WDrq87Ns3QFehfOvffg9HlJSThWl3we6MNmiZNMxjwuQCK',3,2,'active',NULL),(77,'Demo80','demo80@gmail.com','01234567789',NULL,'$2y$10$yV7OZW6S/0sXUnKJf4BUFOq16OUktGEvrWNBWRQGfa6QJC6Mo13.C',1,2,'active',NULL),(78,'Demo 81','demo81@gmail.com','3737293883',NULL,'$2y$10$yCVScdRPgfTpcEIosfw3VOrzXHKk7pNTctBJmKfY0W1YhBXJ9pvia',3,2,'inactive',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-11-24 14:12:27
