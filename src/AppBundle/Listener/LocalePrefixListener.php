<?php

namespace AppBundle\Listener;

class LocalePrefixListener
{
	private static $locales = ['en', 'es'];
	
	public function onKernelRequest(\Symfony\Component\HttpKernel\Event\GetResponseEvent $event)
	{
		$request = $event->getRequest();
		$domain = parse_url($request->getUri())['host'];
		$domainPieces = explode('.', $domain);
		
		if (count($domainPieces) !== 3) {
			return;
		}
		
		if (in_array($domainPieces[0], self::$locales)) {
			$request->setLocale($domainPieces[0]);
		}
	}
}
