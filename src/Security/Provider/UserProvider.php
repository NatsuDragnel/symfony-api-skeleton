<?php

namespace App\Security\Provider;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use App\Entity\User;

class UserProvider implements UserProviderInterface
{
	/**
	 * @var EntityManager
	 */
	protected $em;
	
	
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->userRepository = $this->em->getRepository(User::class);
	}
	
	
	public function loadUserByUsername($username)
	{
		$user = $this->userRepository
        		  ->createQueryBuilder('u')
        		  ->where('u.username = :username OR u.email = :email')
        		  ->setParameter('username', $username)
        		  ->setParameter('email', $username)
        		  ->getQuery()
        		  ->getSingleResult();
	   
		if (!$user instanceof AdvancedUserInterface) {
		    $message = sprintf(
		        'Unable to find an active admin UserBundle:User object identified by "%s".',
		        $username
		    );
		    $ex = new UsernameNotFoundException($message);
		    $ex->setUser($user);
		    throw $ex;
		}
		
// 		if ($user->hasRole('ROLE_USER')) {
// 		    $ex = new AccessDeniedException('Your account is not allowed in this space.');
// 		    throw $ex;
// 		}
		
		if (!$user->isAccountNonLocked()) {
		    $ex = new LockedException('User account is locked.');
		    $ex->setUser($user);
		    throw $ex;
		}
		
		if (!$user->isEnabled()) {
		    $ex = new DisabledException('User account is disabled.');
		    $ex->setUser($user);
		    throw $ex;
		}
		
		if (!$user->isAccountNonExpired()) {
		    $ex = new AccountExpiredException('User account has expired.');
		    $ex->setUser($user);
		    throw $ex;
		}
		
		if (!$user->isCredentialsNonExpired()) {
		    $ex = new CredentialsExpiredException('User credentials has expired.');
		    $ex->setUser($user);
		    throw $ex;
		}
	
		return $user;
	}
	
	public function refreshUser(UserInterface $user)
	{
		$class = get_class($user);
		if (!$this->supportsClass($class)) {
			throw new UnsupportedUserException(
				sprintf(
					'Instances of "%s" are not supported.',
					$class
				)
			);
		}

		return $this->userRepository->find($user->getId());
	}
	
	public function supportsClass($class)
	{
		return $this->userRepository->getClassName() === $class
		|| is_subclass_of($class, $this->userRepository->getClassName());
	}
	
}