﻿INSERT IGNORE INTO `fs_foodsaver` VALUES (151030,0,'',0,'0000-00-00 00:00:00','',0,0,0,0,'10557','Berlin','52.5250839','13.369402','',0,'user1@example.com','bafc1bcb62e51692a32aeb717ccc8a42','User',NULL,'One','Europaplatz 1','','','','','',NULL,0,NULL,0,'2016-07-22 20:01:45',0,1,'','',1,'57927ba974bc07.93176475',1,'2016-07-22 20:14:18',0.00,0,0,0.00,0,0,0,100.00,0,'0000-00-00','0000-00-00','','0000-00-00','',0,NULL,0,0),(151031,0,'',0,'0000-00-00 00:00:00','',0,0,0,0,'','Frankfurt an der Oder','52.3472237','14.5505673','',0,'user2@example.com','60448ca3a0d65768f52907f932c84bf1','User',NULL,'Two',' ','','','','','',NULL,0,NULL,0,'2016-07-22 20:15:32',0,1,'','',1,'57927ee4e163f2.22045910',1,'0000-00-00 00:00:00',0.00,0,0,0.00,0,0,0,100.00,0,'0000-00-00','0000-00-00','','0000-00-00','',0,NULL,0,0);
INSERT IGNORE INTO `fs_foodsaver` VALUES (151032,241,'',1,'0000-00-00 00:00:00','',0,0,3,0,'10557','Berlin','52.5250839','13.369402','',0,'userbot@example.com','7c8a4e4fcf07150c5afe439887b4e091','User',NULL,'Bot','Europaplatz 5','','','','','',NULL,0,NULL,0,'2016-07-22 20:01:45',0,1,'','',1,'57927ba974bc07.93176475',1,'2016-07-22 20:14:18',0.00,0,0,0.00,0,0,0,100.00,0,'0000-00-00','0000-00-00','','0000-00-00','',0,NULL,3,0);
INSERT INTO `fs_botschafter` (`foodsaver_id`, `bezirk_id`) VALUES ('151032', '241');
INSERT INTO `fs_foodsaver_has_bezirk` (`foodsaver_id`, `bezirk_id`, `active`) VALUES ('151032', '241', '1');
