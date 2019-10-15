<?php

namespace App\Util;

use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Negociator;

/**
 * FormatUtil 
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class FormatUtil
{
	/**
	 * Pagination and Content negociation
	 *
	 * @param Request $request
	 * @param mixed $resources
	 * @return null|View
	 */
	public static function formatView(Request $request, $response, $code = 200) {
	    $view = new View($response, $code);
		
		if (!$request->get('_format')){
			$format = Negociator::content($request);
			$view->setFormat($format);
		}
	
		return $view;
	}
	
	/**
	 * Merge and compute difference
	 *
	 * @param array $array
	 * @param array $toAdd
	 * @param array $toRemove
	 *
	 * @return NULL|array
	 */
	public static function refreshArray(array $oldItems = null, array $itemsToAdd = null, array $itemsToRemove = null) {
	    $oldItems = $oldItems ?? [];
	    $itemsToAdd = $itemsToAdd ?? [];
	    $itemsToRemove = $itemsToRemove ?? [];
	    
	    $refreshItems = array_merge(array_diff($oldItems, $itemsToRemove), $itemsToAdd);
	    return count($refreshItems) == 0 ? null : array_unique($refreshItems);
	}
}