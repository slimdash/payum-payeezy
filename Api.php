<?php
namespace Payum\Payeezy;

use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;

class Api {
	/**
	 * @var HttpClientInterface
	 */
	protected $client;

	/**
	 * @var array
	 */
	protected $options = array(
		'apiKey' => null,
		'apiSecret' => null,
		'merchantToken' => null,
		'sandbox' => null,
	);

	/**
	 * @param array               $options
	 * @param HttpClientInterface $client
	 *
	 * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
	 */
	public function __construct(array $options, HttpClientInterface $client) {
		$options = ArrayObject::ensureArrayObject($options);
		$options->defaults($this->options);
		$options->validateNotEmpty(array(
			'apiKey',
			'apiSecret',
			'merchantToken',
		));
		if (false == is_bool($options['sandbox'])) {
			throw new LogicException('The boolean sandbox option must be set.');
		}
		$this->options = $options;
		$this->client = $client ?: HttpClientFactory::create();
	}

	/**
	 * @return string
	 */
	protected function getApiEndpoint() {
		return $this->options['sandbox'] ? 'https://api-cert.payeezy.com/v1/transactions' : 'https://api.payeezy.com/v1/transactions';
	}

	/**
	 * Payeezy
	 *
	 * HMAC Authentication
	 */
	public function hmacAuthorizationToken($payload) {
		$nonce = strval(hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstrong))));
		$timestamp = strval(time() * 1000); //time stamp in milli seconds
		$data = self::$options['apiKey'] . $nonce . $timestamp . self::$options['merchantToken'] . $payload;
		$hashAlgorithm = "sha256";
		$hmac = hash_hmac($hashAlgorithm, $data, self::$options['apiSecret'], false); // HMAC Hash in hex
		$authorization = base64_encode($hmac);

		return array(
			'authorization' => $authorization,
			'nonce' => $nonce,
			'timestamp' => $timestamp,
			'apikey' => self::$options['apiKey'],
			'token' => self::$options['merchantToken'],
		);
	}

	/**
	 * @param array   $fields
	 * @param string  $transaction_id
	 *
	 * @return array
	 */
	public function doRequest($fields = array(), $transaction_id = null) {
		$url = $this->getApiEndpoint();
		if (isset($transaction_id)) {
			$url = $url . '/' . $transaction_id;
		}
		$method = 'POST';
		$payload = json_encode($fields, JSON_FORCE_OBJECT);
		$headerArray = $this->hmacAuthorizationToken($payload);
		$headers = array_merge(array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		), $headerArray);
		$request = new Request($method, $url, $headers, $payload);
		$response = $this->client->send($request);
		if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
			throw HttpException::factory($request, $response);
		}
		$result = json_decode($response->getBody()->getContents());
		if (null === $result) {
			throw new LogicException("Response content is not valid json: \n\n{$response->getBody()->getContents()}");
		}
		return $result;
	}
}
