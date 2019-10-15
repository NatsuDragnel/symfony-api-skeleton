<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function register(Request $request, EntityManagerInterface $em)
    {
        $data = $request->request->all();

        $constraint = new Assert\Collection(array(
            // the keys correspond to the keys in the input array
            'firstName' => new Assert\Length(array('min' => 1)),
            'lastName' => new Assert\Length(array('min' => 1)),
            'username' => new Assert\Length(array('min' => 1)),
            'password' => new Assert\Length(array('min' => 1)),
            'email' => new Assert\Email(),
        ));
        
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraint);

        if ($violations->count() > 0) {
            return new JsonResponse((string)$violations, 400);
        }

        try {
            $user = new User();
            $user
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setUsername($data['username'])
                ->setPlainPassword($data['password'])
                ->setEmail($data['email'])
                ->setEnabled($data['enabled'] ?? false)
                ->setRoles([User::ROLE_DEFAULT])
            ;
            
            $user->setPassword(hash('sha512', $data['password']));
            
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
        
        return new JsonResponse(['id' => $user->getId(), 'name' => $user->getFullName()]);
    }
}

?>