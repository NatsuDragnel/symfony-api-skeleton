<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\ParameterBag;
use App\Entity\User;

/**
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class RequestUtil
{
	public static function blameRequestQuery(ParameterBag $query, User $user) {
	    $query->set('createdBy', $user->getId());
	    return $query;
	}
	
	public static function matchFields($object, $authorizedField, $data) {
	    $requestFields = array_keys($data);
	    
	    foreach ($requestFields as $field) {
	        if (true === in_array($field, $authorizedField) && false === empty($data[$field])) {
	            $object->{'set'.ucwords($field)}($data[$field]);
	        }
	    }
	    
	    return $object;
	}
}