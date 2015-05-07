CREATE TABLE  `manolev_assoc`.`exh_dimensions` (
`id` INT NOT NULL AUTO_INCREMENT ,
`label` VARCHAR( 50 ) NOT NULL ,
`width` INT NOT NULL ,
`height` INT NOT NULL ,
`crop` TINYINT NOT NULL DEFAULT  '0',
PRIMARY KEY (  `id` ) ,
) ENGINE = MYISAM

ALTER TABLE  `exh_dimensions` ADD  `size` VARCHAR( 20 ) NOT NULL AFTER  `label` ;

ALTER TABLE  `exh_dimensions` ADD INDEX (  `label` ) ;
ALTER TABLE  `exh_dimensions` ADD INDEX (  `size` ) ;

