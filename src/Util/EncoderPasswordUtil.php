<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use App\Entity\User;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class EncoderPasswordUtil
{
	/**
	 * sha512 encoder
	 * 
	 * @param string $pass
	 * @param string $salt
	 * @return string
	 */
	public static function sha512(string $pass, string $salt)
	{
		$iterations = 5000; // Par défaut
		$salted = $pass.'{'.$salt.'}';
		
		$digest = hash('sha512', $salted, true);
		for ($i = 1; $i < $iterations; $i++) {
			$digest = hash('sha512', $digest.$salted, true);
		}
		
		return base64_encode($digest);
	}
	
	public static function blameRequestQuery(ParameterBag $query, User $user) {
	    $query->set('createdBy', $user->getId());
	    return $query;
	}
	
	public static function setter($object, $data) {
	    $fields = array_keys($data);
	    
	    foreach ($fields as $field) {
	        if (! empty($data[$field])) {
	            $object->{'set'.ucwords($field)}($data[$field]);
	        }
	    }
	    
	    return $object;
	}
}