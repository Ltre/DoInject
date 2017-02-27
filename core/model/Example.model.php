<?php
/**
 *  CREATE TABLE `dm_example` (
 *   `test_id` bigint(20) NOT NULL AUTO_INCREMENT,
 *   `v` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
 *   PRIMARY KEY (`test_id`)
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
 */
class ExampleModel extends DIModel {
    //这里不必对$this->table声明初始值，默认为前缀"dm_"连接"example"形成的表名
}