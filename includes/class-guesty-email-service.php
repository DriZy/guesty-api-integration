<?php

class EmailService {

    public function __construct() {}

    public function sendReservationEmail($toEmail, $subject, $body) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($toEmail, $subject, $body, $headers);
    }
}

add_action( 'phpmailer_init', 'guesty_phpmailer_smtp_config' );

function guesty_phpmailer_smtp_config( $phpmailer ) {
    $smtp_host = get_option( 'guesty_api_smtp_host' );
    $smtp_port = get_option( 'guesty_api_smtp_port' );
    $smtp_username = get_option( 'guesty_api_smtp_username' );
    $smtp_password = get_option( 'guesty_api_smtp_password' );

    if ( $smtp_host && $smtp_port && $smtp_username && $smtp_password ) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $smtp_host;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $smtp_port;
        $phpmailer->Username = $smtp_username;
        $phpmailer->Password = $smtp_password;
        $phpmailer->SMTPSecure = 'tls';
    }
}
