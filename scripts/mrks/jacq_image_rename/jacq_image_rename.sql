SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE `jacq_image_rename` (
  `id` int(11) NOT NULL,
  `orig_path` varchar(500) DEFAULT NULL,
  `orig_filename` varchar(100) DEFAULT NULL,
  `orig_time` timestamp NULL DEFAULT NULL,
  `new_path` varchar(500) DEFAULT NULL,
  `new_filename` varchar(100) DEFAULT NULL,
  `qr_code` varchar(500) DEFAULT NULL,
  `base_url` varchar(100) DEFAULT NULL,
  `acronym` varchar(50) DEFAULT NULL,
  `number` varchar(50) DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `user` varchar(150) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `jacq_image_rename`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `jacq_image_rename`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
