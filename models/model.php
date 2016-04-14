<?php
class Model
{
    private static $settings;

    public static function getSettings()
    {
        if (count(Model::$settings) == 0) {
            $db = Database::singleton();
            $settings = array();
            try {
                $result = $db->query("SELECT `key`, `value` FROM settings;");
                while ($row = $db->fetch_assoc($result)) {
                    Model::$settings[$row["key"]] = $row["value"];
                }
            } catch (Exception $e) {
                // do nothing.
            }
        }

        return Model::$settings;
    }

    public static function initialSetup()
    {
        $db = Database::singleton();
        if (!Model::tableExists('authorizedEmails')) {
            $db->query("CREATE TABLE `authorizedEmails` (
                          `email` varchar(255) NOT NULL,
                          PRIMARY KEY (`email`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
            // sample data
            $db->query("INSERT INTO `authorizedEmails`(`email`) VALUES
                          ('first.email@university.edu'),
                          ('second.email@university.edu');");
        }

        if (!Model::tableExists('responses')) {
            $db->query("CREATE TABLE `responses` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) NOT NULL,
                          `email` varchar(255) NOT NULL,
                          `pickedDate` varchar(255) NOT NULL,
                          PRIMARY KEY (`id`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        }

        if (!Model::tableExists('settings')) {
            $db->query("CREATE TABLE `settings` (
                          `key` varchar(20) NOT NULL,
                          `value` varchar(20000) NOT NULL,
                          PRIMARY KEY (`key`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
            // sample data
            $db->query("INSERT INTO `settings`(`key`,`value`) VALUES
                          ('contactEmail','my.email@university.edu'),
                          ('contactName','Contact Name'),
                          ('formDescription','Goto /admin for updating these values. Please choose the date you would like to present in the panel. \r\n\r\nNote that student panels are group presentations, so we will expect the participants to meet beforehand to discuss how & what they will talk about.\r\n\r\nThanks!'),
                          ('formTitle','Student Panels for CLASSNAME: Name of the class'),
                          ('pinCode','Some simple pin'),
                          ('siteTitle','Student Panels Scheduling');");
        }

        if (!Model::tableExists('slots')) {
            $db->query("CREATE TABLE `slots` (
                          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) NOT NULL,
                          `capacity` int(11) NOT NULL,
                          PRIMARY KEY (`id`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
            // sample data
            $db->query("INSERT INTO `slots`(`id`,`name`,`capacity`) VALUES
                          (1,'Week 3 October 7',5),
                          (2,'Week 4 October 14',5),
                          (3,'Week 5 October 21',5);");


        }
    }

    private static function tableExists($tableName)
    {
        $db = Database::singleton();
        $checktable =  $db->query("SHOW TABLES LIKE '$tableName'");
        return $db->num_rows($checktable) > 0;
    }
}