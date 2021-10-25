# WOOCOMMERCE SARGAPAY
 
WooCommerce plugin para pagar usando Cardano ADA.
 
## Instalación
 
Descarga el archivo zip y usa el panel de administración de Wordpress para instalarlo.
 
## ¿Cómo obtener la xpub? [extended public key]
 
### Yoroi
 
Ve a configuración y en la opción de billetera en la sección de exportar billetera se encuentra el botón de exportar, se abrirá una ventana emergente debajo del QR se encuentra la xpub.
Formato de xpub hexadecimal
 
### Adalite
 
Selecciona la opción Advanced  y copia la Shelley extended public key.
Formato de xpub hexadecimal
 
### Dedalus
 
Selecciona la opción de More y selecciona la opción de Settings en la sección de Wallet Public Key click en el ojo para revelar la xpub.
Formato de xpub acct_xvk
 
 
## ¿Cómo Configurar?
 
En el panel de administración de Wordpress en la sección de WooCommerce en la sección de pagos seleccionamos WooCommerce Sargapay.
 
Ya que nos encontramos en los ajustes del plugin lo primero que tenemos que hacer es configurar la Llave pública y verificamos que las direcciones que genera concuerdan con la wallet donde queremos recibir los pagos.
 
Ya que tenemos la llave pública el siguiente paso es seleccionar las confirmaciones necesarias para tomar un pago como válido.
 
Ahora tenemos que ir a Blockforst.io para obtener 2 api key una para testnet y otra para mainnet.
 
Seleccionar una moneda en caso que la moneda seleccionada del sitio no esté soportada.
 
Por último tenemos que activar el plugin y verificar la red que vamos usar, si activas la casilla el plugin generará direcciones de pago para testnet y de lo contrario para mainnet.
 
 
## License

https://github.com/sargatxet/WooCommerce-sargapay/blob/main/LICENSE
