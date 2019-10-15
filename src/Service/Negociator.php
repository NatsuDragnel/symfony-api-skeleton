<?php

namespace App\Service;

use Negotiation\LanguageNegotiator;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Negociator
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class Negociator
{
	/**
	 * Content Negociation
	 * 
	 * @param Request $request
	 * @return string
	 */
	public static function content(Request $request)
	{
	    $requestStack = new RequestStack();
	    $requestStack->push($request);
	    
		$acceptHeader = $request->headers->get('accept');
		$priorities   = array('application/json', 'application/xml','text/html');
		
		$negociator = new FormatNegotiator($requestStack);
		$format = $negociator->getBest($acceptHeader, $priorities);
		$splitFormat = preg_split('#/#', $format->getValue());
		
		return $splitFormat[1];
	}
	
	
	/**
	 * Language Negotiation
	 * 
	 * @param Request $request
	 * @return AcceptHeader | null
	 */
	public static function language(Request $request)
	{
		$acceptLanguage = $request->headers->get('accept-language');
		$priorities   = array('fr', 'en');
		
		$negociator = new LanguageNegotiator();
		$language = $negociator->getBest($acceptLanguage, $priorities);
		
		return $language;
	}
	
	
	/**
	 * Enconding Negociation
	 * 
	 * @param Request $request
	 * @return AcceptHeader | null
	 */
	public static function encoding(Request $request)
	{
	    $acceptEncoding = $request->headers->get('accept-encoding');
		$priorities   = array('utf-8', 'ISO-8859-1');
		
		$negociator = new LanguageNegotiator();
		$encoding = $negociator->getBest($acceptEncoding, $priorities);
		
		return $encoding;
	}
}