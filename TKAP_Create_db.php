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

function SARGAPAY_create_address_table()
{
    global $wpdb;
    $tableName = $wpdb->prefix . 'wc_sarga_address';

    $query = "CREATE TABLE IF NOT EXISTS `$tableName`
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
            `testnet` BOOLEAN,         
            PRIMARY KEY (`id`)
        );";

    $wpdb->query($query);
}
