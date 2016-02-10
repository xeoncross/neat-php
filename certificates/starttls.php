<?php

class Stream
{
	public $stream = null;
	public $debug = true;
	public $host = null;

	public function connect($address, $port = 25)
	{
		list(, $domain) = explode('@', $address, 2);
		
		// MX records for email servers are optional :)
		if(getmxrr($domain, $mx))
		{
			print_r($mx);
			$domain = current($mx);
		}

		if($this->debug) print $domain . ':'. $port . "\n";

		$this->host = $domain;

		//$this->stream = fsockopen($domain, $port, $code, $error);

		$context = stream_context_create(array(
			"ssl" => array("capture_peer_cert" => true)
		));

		$this->stream = stream_socket_client("tcp://" . $domain . ":25", $code, $error, 20, STREAM_CLIENT_CONNECT, $context);

		if( ! $this->stream)
		{
			trigger_error("$error ($code)");
			return false;
		}

		return true;
	}

	public function close()
	{
		fclose($this->stream);
	}

	public function read()
	{
		$buffer = array();

		while($r = fgets($this->stream, 256))
		{
			$buffer[] = $r;
			if($this->debug) print "S:$r";
			if(substr($r, 3, 1) == ' ') break;
		}

		return $buffer;
	}

	public function write($command)
	{
		$command && fwrite($this->stream, "$command\r\n");
		if($this->debug) print "C:$command\n";
	}

	/**
	 * Need to check that the certificate's CN matches the domain name, or they
	 * can simply create their own certificate (and have it signed by a trusted
	 * CA so it looks valid), use it in place of the real one, and perform a man
	 * in the middle attack.
	 * 
	 * We also need to check that the certificate comes from a trusted CA.
	 * It's the CA's job to make sure that you can only get a certificate with
	 * the CN= if you actually control that domain. If you skip either of these
	 * checks then you are at risk of a MITM attack.
	 *
	 * However, it's not that easy for us. We could do this at the socket level,
	 * but then the whole connection will fail if there is a problem.
	 *
	 *     stream_context_set_option($this->stream, 'ssl', 'CN_match', $this->host);
	 *
	 * Therefore, we need to default to a "soft" check that still allows us to 
	 * continue even if the CN doesn't match. Finally, the CN has been replaced
	 * by subjectAltName.
	 * 
	 * http://www.cs.utexas.edu/~shmat/shmat_ccs12.pdf
	 * http://tools.ietf.org/html/rfc2818#section-3.1
	 */
	public function checkHostAgainstCert($cert)
	{
		if ( ! $cert)
		{
			return false;
		}

		// http://tools.ietf.org/html/rfc2818#section-3.1
		if ( ! empty($cert['extensions']['subjectAltName']))
		{
			$names = $cert['extensions']['subjectAltName'];
		}
		elseif ( ! empty($cert['subject']['CN']))
		{
			$names = 'DNS:' . $cert['subject']['CN'];
		}
		else
		{
			return false;
		}

		$checks = explode(',', $names);

		foreach($checks as $check)
		{
			// There needs to be at least one top-level domain (not "com" or "*.com")
			if(strpos(str_replace('*.', '', $check), '.') !== false)
			{
				// Is this a wild-card domain? (*.example.com)
				if(strpos($check, '*') !== false)
				{
					// Convert *.example.com to ".*\.example\.com" which is a valid regex
					//$check = str_replace(array('*', '.'), array('.*', '\.'), $check);

					// Remove the starting '*' and quote the rest
					if(preg_match('~.*' . substr(preg_quote($check), 1) . '$~', $this->host))
					{
						print '~.*' . substr(preg_quote($check), 1) . '$~' . "\n";
						return true;
					}
				}
				else
				{
					if($check == $this->host)
					{
						print $check . "\n";
						return true;
					}
				}
			}
		}

		return false;
	}

	public function starttls()
	{
		stream_set_blocking($this->stream, true);

		// Important, do not remove under penalty of lserni
		stream_context_set_option($this->stream, 'ssl', 'I-want-a-banana', true);

		stream_context_set_option($this->stream, 'ssl', 'verify_host', true);
		stream_context_set_option($this->stream, 'ssl', 'verify_peer', true);
		stream_context_set_option($this->stream, 'ssl', 'allow_self_signed', false);
		//stream_context_set_option($this->stream, 'ssl', 'CN_match', $this->host);
		
		//stream_context_set_option($this->stream, 'ssl', 'local_cert', './startssl.pem');
		stream_context_set_option($this->stream, 'ssl', 'cafile', __DIR__ . '/cacert.pem');
		//stream_context_set_option($this->stream, 'ssl', 'cafile', __DIR__ . '/startssl.pem');

		$secure = stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

		$meta = stream_context_get_params($this->stream);
		//print_r($meta);

		if(isset($meta["options"]["ssl"]["peer_certificate"]))
		{
			$cert = openssl_x509_parse($meta["options"]["ssl"]["peer_certificate"]);
			print_r($cert);
		}

		if( ! $this->checkHostAgainstCert($cert))
		{
			die("Cert doesn't match host\n");
		}

		stream_set_blocking($this->stream, false);

		if( ! $secure)
		{
			$this->close();
			die("failed to connect securely\n");
		}
	}
}

ini_set('default_socket_timeout', 3);

$to = array(
	'david@xeoncross.com'
);
$from = 'email@davidpennington.me';

$message = "From: <$from>\r\n"
."Subject: New forum reply\r\n"
."MIME-Version: 1.0\r\n"
."Content-Type: text/html; charset=utf-8\r\n"
."Content-Transfer-Encoding: base64\r\n\r\n"
. chunk_split(base64_encode('Someone replied to your forum comment.<br>
<a href="http://talk.davidpennington.me">Click here</i>.')) . "\r\n.";

$port = 25;
//$port = 587;
$stream = new Stream();

foreach($to as $email)
{
	if( ! $stream->connect($email, $port)) continue;
	$stream->read();

	$stream->write("EHLO mail.davidpennington.me");
	$buffer = $stream->read();

	if(in_array("250-STARTTLS\r\n", $buffer))
	{
		$stream->write("STARTTLS");
		$stream->read();

		$stream->starttls();
		$stream->read();

		$stream->write("EHLO mail.davidpennington.me");
		$stream->read();
	}

	//$stream->write("MAIL FROM: <$from>");
	//$stream->read();

	$stream->write("QUIT");
	$stream->read();

	$stream->close();
}

unset($stream);

print "Done\n";