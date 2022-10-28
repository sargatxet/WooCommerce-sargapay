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

require "vendor/autoload.php";

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;

class GenerateQR
{
    private $options;

    private static $instances = [];
    protected function __construct()
    {
        $options = new QROptions([
            'version'             => 7,
            'outputType'          => QROutputInterface::GDIMAGE_PNG,
            'eccLevel'            => EccLevel::L,
            'scale'               => 10,
            'imageBase64'         => false,
            'imageTransparent'    => false,
            'drawCircularModules' => true,
            'circleRadius'        => 0.4
        ]);
    }
    protected function __clone()
    {
    }
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
    public static function getInstance(): GenerateQR
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }
    function generate($payAdress)
    {
        try{
            $im = (new QRCode($options))->render($payAdress);
            return  "<div style='display:flex; justify-content:center; padding:10px 0; '><img style='width:10vw;
            height: 10vw;' src=" . $im . " /></div>";
        }
        catch(Throwable $e){
            exit($e->getMessage());
        }
        
    }

    function QR_URL($payAdress)
    {
        try{
            $url = WP_CONTENT_DIR . "/uploads/$payAdress.png";
            $im = (new QRCode($options))->render($payAdress, $url);
            return $url;
        }
        catch(Throwable $e){
            exit($e->getMessage());
        }
    }
}
