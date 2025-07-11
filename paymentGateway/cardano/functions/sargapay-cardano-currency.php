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

/**
 * Add cardano currency and currency symbol
 */
if (!defined('WPINC')) {
    die;
}

function add_sarga_cardano_currency( $currencies ) {
     $currencies['SARGACARDANO'] = __( 'Cardano', 'sargapay' );
     return $currencies;
}

function add_sarga_cardano_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'SARGACARDANO': $currency_symbol = '₳'; break;
     }
     return $currency_symbol;
}