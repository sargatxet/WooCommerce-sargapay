<?php

// @email - Email address of the reciever
// @subject - Subject of the email
// @heading - Heading to place inside of the woocommerce template
// @message - Body content (can be HTML)
// @attachments - Attach Files
function send_email_woocommerce_style($email, $subject, $heading, $message, $path, $name)
{

  // Define headers html emails
  $headers[] = 'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>';
  // Add Embed ID
  if (file_exists($path)) {
    $phpmailerInitAction = function (&$phpmailer) use ($path, $name) {
      $phpmailer->SMTPKeepAlive = true;
      $phpmailer->AddEmbeddedImage($path, 'qrimg', $name);
    };
    add_action('phpmailer_init',  $phpmailerInitAction);
    // Load QR Img
    $attachment = array($path);
  }else{
    write_log("Error: QR File doesn't Exist");
    $attachment = array();
  }

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
