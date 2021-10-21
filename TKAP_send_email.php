<?php

// @email - Email address of the reciever
// @subject - Subject of the email
// @heading - Heading to place inside of the woocommerce template
// @message - Body content (can be HTML)
// @attachments - Attach Files
function send_email_woocommerce_style($email, $subject, $testnet_bool, $total, $address, $path, $name)
{

  // Define headers html emails
  $headers[] = 'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>';
  // Mensaje del Pool Sarga
  $ad  = "<p style='font-weight: bold; text-align: center;'>Tienes Cardano ADA ponlo a trabajar en el pool de staking <a href='https://cardano.sargatxet.cloud/' target='_blank'>SARGATXET</a></p>";
  $ad .= "<table style='text-align:center; margin-left: auto; margin-right: auto;'>
            <tr style='text-align:center;'>              
              <th><a href='https://cardano.sargatxet.cloud/'>Website</a></th>
              <th><a href='https://discord.gg/X6Ruku9q42'>Discord</a></th>
            </tr>  
          </table>";
  $ad .= "<p style='font-weight: bold; text-align: center;'>Powered by Sargatxet</p>";
  // Add Embed ID
  if (file_exists($path)) {
    $phpmailerInitAction = function (&$phpmailer) use ($path, $name) {
      $phpmailer->SMTPKeepAlive = true;
      $phpmailer->AddEmbeddedImage($path, 'qrimg', $name);
    };
    add_action('phpmailer_init',  $phpmailerInitAction);
    // Load QR Img
    $attachment = array($path);
  } else {
    write_log("Error: QR File doesn't Exist");
    $attachment = array();
  }

  $subject = __("Payment Instructions ", 'tk-ada-pay-plugin') . get_bloginfo('name');
  $heading = __("Payment Instructions ", 'tk-ada-pay-plugin');
  $message =  "<h2 style='overflow-wrap:anywhere;'>" . __("You have 24hrs to pay before the order is cancelled", 'tk-ada-pay-plugin') . "<h2>";
  $message .= "</h2><h2>" . esc_html(__('Total Amount in ADA ', 'tk-ada-pay-plugin')) . $total . "</h2>";
  if ($testnet_bool) {
    $message .= "<h3>" . esc_html(__(' TESTNET ADDRESS', 'tk-ada-pay-plugin')) . "</h3>";
  } else {
    $message .= "<h3>" . esc_html(__("Address", 'tk-ada-pay-plugin')) . "</h3>";
  }
  $message .= '<div><img src="cid:qrimg" style="margin-left: auto; margin-right: auto;"></div>';
  $message .= "<p style='font-weight: bold;'>". adrress_break($address)."</p>";
  $message .= "<p>" . esc_html(__('You can verify the payment address and amount if you login and go to my-account/orders', 'tk-ada-pay-plugin')) . "</p>";
  $message .= $ad;
  // Get woocommerce mailer from instance
  $mailer = WC()->mailer();

  // Wrap message using woocommerce html email template
  $wrapped_message = $mailer->wrap_message($heading, $message);

  // Create new WC_Email instance
  $wc_email = new WC_Email;

  // Style the wrapped message with woocommerce inline styles
  $html_message = $wc_email->style_inline($wrapped_message);

  // Send the email using wordpress mail function
  add_filter('wp_mail_content_type', 'my_custom_email_content_type');
  wp_mail($email, $subject, $html_message, $headers, $attachment);
  // Clean Attachments and header
  remove_filter('wp_mail_content_type', 'my_custom_email_content_type');
  if (file_exists($path)) {
    remove_action('phpmailer_init', $phpmailerInitAction);
    unlink($path);
  }
}

function my_custom_email_content_type()
{
  return 'text/html';
}

function adrress_break($address) {
  $new_address = '';
  $address_1 = explode('>',$address);
  $sizeof = sizeof($address_1);
  for ($i=0; $i<$sizeof; ++$i) {
      $address_2 = explode('<',$address_1[$i]);
      if (!empty($address_2[0])) {
          $new_address .= preg_replace('#([^\n\r .]{60})#i', '\\1  ', $address_2[0]);
      }
      if (!empty($address_2[1])) {
          $new_address .= '<' . $address_2[1] . '>';   
      }
  }
  return $new_address;
}
