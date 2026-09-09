<?php
add_action('phpmailer_init', 'configure_smtp');
function configure_smtp($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $phpmailer->Username   = defined('SMTP_USER') ? SMTP_USER : '';
    $phpmailer->Password   = defined('SMTP_PASS') ? SMTP_PASS : '';
    $phpmailer->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
    $phpmailer->From       = defined('SMTP_FROM') ? SMTP_FROM : '';
    $phpmailer->FromName   = defined('SMTP_NAME') ? SMTP_NAME : '';
}
