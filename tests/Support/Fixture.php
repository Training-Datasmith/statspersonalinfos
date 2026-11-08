<?php

class Fixture
{
    private static $tables = array(
        'customer',
        'gender',
        'address',
        'country',
        'country_lang',
        'orders',
        'currency',
        'currency_lang',
        'lang',
    );

    public static function ensureSchema()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;

        $queries = array(
            "CREATE TABLE IF NOT EXISTS `{$prefix}customer` (
                `id_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_gender` INT UNSIGNED NOT NULL DEFAULT 0,
                `id_shop` INT UNSIGNED NOT NULL,
                `id_shop_group` INT UNSIGNED NOT NULL,
                `birthday` DATE NULL,
                PRIMARY KEY (`id_customer`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}gender` (
                `id_gender` INT UNSIGNED NOT NULL,
                `type` TINYINT NOT NULL,
                PRIMARY KEY (`id_gender`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}address` (
                `id_address` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_customer` INT UNSIGNED NOT NULL,
                `id_country` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}country` (
                `id_country` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_country`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}country_lang` (
                `id_country` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                PRIMARY KEY (`id_country`, `id_lang`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}orders` (
                `id_order` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT UNSIGNED NOT NULL,
                `id_shop_group` INT UNSIGNED NOT NULL,
                `id_currency` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}currency` (
                `id_currency` INT UNSIGNED NOT NULL,
                `iso_code` CHAR(3) NOT NULL,
                PRIMARY KEY (`id_currency`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}currency_lang` (
                `id_currency` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                PRIMARY KEY (`id_currency`, `id_lang`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}lang` (
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                PRIMARY KEY (`id_lang`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        );

        foreach ($queries as $sql) {
            $db->executeS($sql);
        }
    }

    public static function truncateAll()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;

        mysqli_query($db->getLink(), 'SET FOREIGN_KEY_CHECKS=0');
        foreach (self::$tables as $table) {
            mysqli_query($db->getLink(), 'TRUNCATE TABLE `' . $prefix . $table . '`');
        }
        mysqli_query($db->getLink(), 'SET FOREIGN_KEY_CHECKS=1');
    }

    public static function applySessionClock()
    {
        $db = Db::getInstance();
        $link = $db->getLink();
        mysqli_query($link, "SET time_zone = '+00:00'");
        mysqli_query($link, "SET TIMESTAMP = UNIX_TIMESTAMP('2016-06-15 12:00:00')");
    }

    public static function insertGender($idGender, $type)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'gender` (`id_gender`, `type`) VALUES (' .
            (int) $idGender . ', ' . (int) $type . ')'
        );
    }

    public static function insertCustomer($idGender, $idShop, $idShopGroup, $birthday)
    {
        $prefix = _DB_PREFIX_;
        $birthdaySql = 'NULL';
        if ($birthday !== null) {
            if ($birthday === '0000-00-00') {
                $birthdaySql = "'0000-00-00'";
            } else {
                $birthdaySql = "'" . mysqli_real_escape_string(Db::getInstance()->getLink(), $birthday) . "'";
            }
        }

        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'customer` (`id_gender`, `id_shop`, `id_shop_group`, `birthday`) VALUES (' .
            (int) $idGender . ', ' . (int) $idShop . ', ' . (int) $idShopGroup . ', ' . $birthdaySql . ')'
        );
    }

    public static function insertCountry($idCountry, $nameLang1, $nameLang2)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS('INSERT INTO `' . $prefix . 'country` (`id_country`) VALUES (' . (int) $idCountry . ')');
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'country_lang` (`id_country`, `id_lang`, `name`) VALUES (' .
            (int) $idCountry . ', 1, \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $nameLang1) . '\')'
        );
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'country_lang` (`id_country`, `id_lang`, `name`) VALUES (' .
            (int) $idCountry . ', 2, \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $nameLang2) . '\')'
        );
    }

    public static function insertAddress($idCustomer, $idCountry)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'address` (`id_customer`, `id_country`) VALUES (' .
            (int) $idCustomer . ', ' . (int) $idCountry . ')'
        );
    }

    public static function insertCurrency($idCurrency, $isoCode, $nameLang1, $nameLang2)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'currency` (`id_currency`, `iso_code`) VALUES (' .
            (int) $idCurrency . ', \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $isoCode) . '\')'
        );
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'currency_lang` (`id_currency`, `id_lang`, `name`) VALUES (' .
            (int) $idCurrency . ', 1, \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $nameLang1) . '\')'
        );
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'currency_lang` (`id_currency`, `id_lang`, `name`) VALUES (' .
            (int) $idCurrency . ', 2, \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $nameLang2) . '\')'
        );
    }

    public static function insertLang($idLang, $name)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'lang` (`id_lang`, `name`) VALUES (' .
            (int) $idLang . ', \'' . mysqli_real_escape_string(Db::getInstance()->getLink(), $name) . '\')'
        );
    }

    public static function insertOrder($idShop, $idShopGroup, $idCurrency, $idLang)
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->executeS(
            'INSERT INTO `' . $prefix . 'orders` (`id_shop`, `id_shop_group`, `id_currency`, `id_lang`) VALUES (' .
            (int) $idShop . ', ' . (int) $idShopGroup . ', ' . (int) $idCurrency . ', ' . (int) $idLang . ')'
        );
    }
}
