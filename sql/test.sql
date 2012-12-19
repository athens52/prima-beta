/*
MySQL Data Transfer
Source Host: localhost
Source Database: test
Target Host: localhost
Target Database: test
Date: 19.12.2012 21:33:02
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for cs_cell
-- ----------------------------
CREATE TABLE `cs_cell` (
  `id` int(11) NOT NULL auto_increment,
  `field_id` int(11) NOT NULL,
  `sign` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `update_date` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Table structure for cs_field
-- ----------------------------
CREATE TABLE `cs_field` (
  `id` int(11) NOT NULL auto_increment,
  `state` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=cp1251;

