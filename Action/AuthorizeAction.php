<?php
namespace Payum\Payeezy\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Authorize;
use Payum\Payeezy;

class AuthorizeAction extends Api\BaseApiAwareAction {
	/**
	 * {@inheritDoc}
	 *
	 * @param Authorize $request
	 */
	public function execute($request) {
		/* @var $request Authorize */
		RequestNotSupportedException::assertSupports($this, $request);
		$details = ArrayObject::ensureArrayObject($request->getModel());
		$details['transaction_type'] = 'authorize';
		if (!isset($details['method'])) {
			$details['method'] = 'credit_card';
		}

		$this->api->doRequest($details->toUnsafeArray());
		$model->replace((array) $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports($request) {
		return
		$request instanceof Authorize &&
		$request->getModel() instanceof \ArrayAccess
		;
	}
}