<?php

namespace App\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;

/**
 * ID Generator
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class IdGenerator extends AbstractIdGenerator
{
	static $cache = array();
	
	public function generate(EntityManager $entityManager, $entity)
	{
		$prefix = mb_substr(md5(uniqid()), 0, 10);
		$date = new \DateTime();
		$value = $prefix.$date->getTimestamp();

		return $value;
	}
}