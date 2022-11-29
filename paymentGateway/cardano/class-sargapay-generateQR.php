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

use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

if (!defined('WPINC')) {
    die;
}

class Sargapay_GenerateQR
{
    private $options;

    private static $instances = [];
    protected function __construct()
    {
        $this->options = new QROptions([
            'eccLevel' => QRCode::ECC_L,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'version' => 10,
        ]);
    }
    protected function __clone()
    {
    }
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
    public static function getInstance(): Sargapay_GenerateQR
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }
    function generate($payAdress)
    {
        try {
            $url = WP_CONTENT_DIR . "/uploads/$payAdress.png";
            $img_url = (new QRCode($this->options))->render($payAdress, $url);
?>
            <div style="display:flex; justify-content:center; padding:10px 0;">
                <img style="width:10vw; height: 10vw;" src="<?php echo esc_url($img_url) ?>" />
            </div>
<?php
        } catch (Throwable $e) {
            exit($e->getMessage());
        }
    }

    function QR_URL($payAdress)
    {
        try {
            $url = WP_CONTENT_DIR . "/uploads/$payAdress.png";
            if (!file_exists($url)) {
                (new QRCode($this->options))->render($payAdress, $url);
            }
            return $url;
        } catch (Throwable $e) {
            exit($e->getMessage());
        }
    }
}
