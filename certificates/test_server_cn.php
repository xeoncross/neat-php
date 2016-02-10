<?php

// Good
$host = 'www.google.com';
//$host = 'onlinessl.netlock.hu';
$host = 'alt4.gmail-smtp-in.l.google.com';

// Bad
//$host = 'google.com';
//$host = 'tv.eurosport.com';

$context = stream_context_create(array(
	"ssl" => array("capture_peer_cert" => true)
));

$r = stream_socket_client("tcp://$host:25", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);

if( ! $r)
{
	die("$host - $errstr ($errno)\n");
}

stream_context_set_option($r, 'ssl', 'verify_host', true);
stream_context_set_option($r, 'ssl', 'verify_peer', true);
stream_context_set_option($r, 'ssl', 'allow_self_signed', false);
stream_context_set_option($r, 'ssl', 'CN_match', $host);

//stream_context_set_option($r, 'ssl', 'local_cert', './startssl.pem');
stream_context_set_option($r, 'ssl', 'cafile', __DIR__ . '/cacert.pem');
//stream_context_set_option($r, 'ssl', 'cafile', __DIR__ . '/startssl.pem');

$secure = stream_socket_enable_crypto($r, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

if( ! $secure)
{
	die("failed to connect securely\n");
}

$meta = stream_context_get_params($r);

print_r($meta);

if(empty($meta["options"]["ssl"]))
{
	die("Problem with cert\n");
}

$cert = openssl_x509_parse($meta["options"]["ssl"]["peer_certificate"]);

print_r($cert);

/*
if(isset($cert['subject']['CN']) AND $cert['subject']['CN'] == $host)
{
	print "Valid cert for $host\n";
}
else
{
	print "Invalid cert for $host\n";
}
*/

fclose($r);
