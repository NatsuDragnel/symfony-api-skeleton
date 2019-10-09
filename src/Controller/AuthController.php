<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Util\TokenGenerator;

class AuthController extends AbstractController
{
    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function register(Request $request, EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher)
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
            return new JsonResponse(["error" => (string)$violations], 500);
        }

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

        try {
            $user->setPassword(hash('sha512', $user->getPlainPassword()));
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], 500);
        }
        
        return new JsonResponse(["success" => $user->getUsername(). " has been registered!"], 200);
    }
}

?>