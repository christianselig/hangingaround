-- Code to create the "users" table

CREATE TABLE `users` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `username` varchar(12) NOT NULL DEFAULT '',
  `email` varchar(32) NOT NULL DEFAULT '',
  `password` char(60) NOT NULL DEFAULT '',
  `wins` int(4) NOT NULL DEFAULT '0',
  `losses` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)