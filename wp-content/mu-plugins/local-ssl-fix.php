<?php
/**
 * Plugin Name: Local SSL fix (XAMPP)
 * Description: Uses XAMPP's certificate bundle for WordPress downloads, so installs/updates work behind an SSL-inspecting network. Does nothing if the file is missing (e.g. on live hosting).
 */

add_filter('http_request_args', function ($args) {
    $bundle = 'C:/xampp2/php/cacert.pem';
    if (file_exists($bundle)) {
        $args['sslcertificates'] = $bundle;
    }
    return $args;
});
