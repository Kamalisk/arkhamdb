<?php

namespace AppBundle\Listener;

class CoreExceptionListener
{
	/**
	 * Handles security related exceptions.
	 *
	 * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event An GetResponseForExceptionEvent instance
	 */
	public function onCoreException(\Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event)
	{
		$exception = $event->getException();
		$request = $event->getRequest();
		if (! $request->isXmlHttpRequest()) {
			return;
		}
		$statusCode = $exception->getCode();
		if (!array_key_exists($statusCode, \Symfony\Component\HttpFoundation\Response::$statusTexts)) {
			$statusCode = 500;
		}
		$content = [
				'success' => false, 
				'message' => $exception->getMessage()
		];
		$response = new \Symfony\Component\HttpFoundation\JsonResponse($content, $statusCode, array('Content-Type' => 'application/json'));
		$event->setResponse($response);
	}
}