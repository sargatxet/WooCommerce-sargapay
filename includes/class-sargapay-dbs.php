<?php
/*
    SargaPay. Cardano gateway plug-in for Woocommerce. 
    Copyright (C) 2021  Sargatxet Pools

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
class Sargapay_DBs
{
    protected static $wpdb;
    private static $table_ADA, $table_ONE, $table_ONE_last_tx;

    public function __construct()
    {
        global $wpdb;
        SELF::$wpdb = &$wpdb;
        SELF::$table_ADA = $wpdb->prefix . "sargapay_ada_address";
        SELF::$table_ONE = $wpdb->prefix . "sargapay_one_address";
        SELF::$table_ONE_last_tx = $wpdb->prefix . 'sargapay_one_last_tx';
    }

    private function sargapay_one_tables()
    {

        $query = "CREATE TABLE IF NOT EXISTS " . SELF::$table_ONE . "
        (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,        
            `height` bigint(20) NOT NULL DEFAULT '0',
            `tx_hash` char(199) NULL UNIQUE,
            `pay_address` char(199) NOT NULl,
            `status_order` char(24)  NOT NULL DEFAULT 'on-hold',
            `last_checked` bigint(20) NOT NULL DEFAULT '0',
            `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `order_id` bigint(10) NULL,            
            `order_amount` char(199) NOT NULL,
            `one_price` decimal(32, 6) NOT NULL DEFAULT '0.00',
            `currency` text NULL,
            `network` BOOLEAN,         
            PRIMARY KEY (`id`)
        );";
        SELF::$wpdb->query($query);

        # Last Transaction checked save
        $query = "CREATE TABLE IF NOT EXISTS " . SELF::$table_ONE_last_tx . "
        (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,           
            `tx_hash` char(199) NOT NULL UNIQUE,
            `pay_address` char(199) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `network` BOOLEAN NOT NULL,         
            PRIMARY KEY (`id`)
        );";
        SELF::$wpdb->query($query);
    }

    private function sargapay_ada_tables()
    {
        $query = "CREATE TABLE IF NOT EXISTS " . SELF::$table_ADA . "
        (
            `id` bigint(12) unsigned NOT NULL AUTO_INCREMENT,
            `mpk` char(150) NOT NULL,
            `address_index` bigint(12) NOT NULL,
            `pay_address` char(199) NOT NULL UNIQUE,
            `status` char(24)  NOT NULL DEFAULT 'on-hold',
            `last_checked` bigint(20) NOT NULL DEFAULT '0',
            `assigned_at` bigint(20) NOT NULL DEFAULT '0',
            `order_id` bigint(10) NULL,            
            `order_amount` decimal(32, 6) NOT NULL DEFAULT '0.00',
            `ada_price` decimal(32, 6) NOT NULL DEFAULT '0.00',
            `currency` text NULL,
            `network` BOOLEAN,         
            PRIMARY KEY (`id`)
        );";
        SELF::$wpdb->query($query);
    }

    static function get_sargapay_ada_tables()
    {
        return SELF::$table_ADA;
    }

    static function get_sargapay_one_tables()
    {
        $tables = new stdClass;
        $tables->address_table = SELF::$table_ONE;
        $tables->last_tx_table = SELF::$table_ONE_last_tx;
        return $tables;
    }

    public function run()
    {
        SELF::sargapay_ada_tables();
        SELF::sargapay_one_tables();
    }
}
