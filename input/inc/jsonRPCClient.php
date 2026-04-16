<?php
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

	/**
	 * Debug state
	 *
	 * @var boolean
	 */
	private $debug;

	/**
	 * The server URL
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Optional proxy placeholder for compatibility with the legacy implementation.
	 *
	 * @var string
	 */
	private $proxy = '';

	/**
	 * The request id
	 *
	 * @var integer
	 */
	private $id;

	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	private $notification = false;

	/**
	 * Takes the connection parameters
	 *
	 * @param string $url
	 * @param boolean $debug
	 */
	public function __construct($url, $debug = false) {
		// server URL
		$this->url = $url;
		// proxy
		empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
		// debug state
		empty($debug) ? $this->debug = false : $this->debug = true;
		// message id
		$this->id = '1';
	}

	/**
	 * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	public function setRPCNotification($notification) {
		empty($notification) ?
							$this->notification = false
							:
							$this->notification = true;
	}

	private function isLocalEnvironment() {
		$hostHeader = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$normalizedHost = strtolower(preg_replace('/:.*/', '', $hostHeader));
		return in_array($normalizedHost, array('localhost', '127.0.0.1', '::1'), true);
	}

	private function requestViaCurl($request, &$transportError = null) {
		if (!function_exists('curl_init')) {
			$transportError = 'cURL extension not available';
			return false;
		}

		$verifySsl = !$this->isLocalEnvironment();
		$curl = curl_init($this->url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $verifySsl);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($verifySsl) ? 2 : 0);

		$response = curl_exec($curl);
		if ($response === false) {
			$transportError = curl_error($curl);
		}
		curl_close($curl);

		return $response;
	}

	private function requestViaStream($request, &$transportError = null) {
		$verifySsl = !$this->isLocalEnvironment();
		$opts = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-type: application/json\r\n",
				'content' => $request,
				'timeout' => 20,
				'ignore_errors' => true,
			),
			'ssl' => array(
				'verify_peer' => $verifySsl,
				'verify_peer_name' => $verifySsl,
				'allow_self_signed' => false,
			),
		);
		$context = stream_context_create($opts);

		$phpError = null;
		set_error_handler(function ($severity, $message) use (&$phpError) {
			$phpError = $message;
			return true;
		});
		$fp = fopen($this->url, 'r', false, $context);
		restore_error_handler();

		if (!$fp) {
			$transportError = ($phpError) ? $phpError : 'stream request failed';
			return false;
		}

		$response = '';
		while ($row = fgets($fp)) {
			$response .= trim($row) . "\n";
		}
		fclose($fp);

		return $response;
	}

	/**
	 * Performs a jsonRCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method, $params) {
		$debug = '';

		// check
		if (!is_scalar($method)) {
			throw new Exception('Method name has no scalar value');
		}

		// check
		if (is_array($params)) {
			// no keys
			$params = array_values($params);
		} else {
			throw new Exception('Params must be given as array');
		}

		// sets notification or request task
		if ($this->notification) {
			$currentId = NULL;
		} else {
			$currentId = $this->id;
		}

		// prepares the request
		$request = array(
						'method' => $method,
						'params' => $params,
						'id' => $currentId
						);
		$request = json_encode($request);
		if ($this->debug) {
			$debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";
		}

		$response = false;
		$curlError = null;
		$streamError = null;

		$response = $this->requestViaCurl($request, $curlError);
		if ($response === false) {
			$response = $this->requestViaStream($request, $streamError);
		}

		if ($response === false) {
			$message = 'Unable to connect to ' . $this->url;
			if ($curlError) {
				$message .= ' (cURL: ' . $curlError . ')';
			}
			if ($streamError) {
				$message .= ' (stream: ' . $streamError . ')';
			}
			throw new Exception($message);
		}

		if ($this->debug) {
			$debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
		}
		$response = json_decode($response, true);

		// debug output
		if ($this->debug) {
			echo nl2br($debug);
		}

		// final checks and return
		if (!$this->notification) {
			// check
			if ($response['id'] != $currentId) {
				throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
			}
			if (!empty($response['error'])) {
				throw new Exception('Request error: ' . $response['error']);
			}

			return $response['result'];

		} else {
			return true;
		}
	}
}
