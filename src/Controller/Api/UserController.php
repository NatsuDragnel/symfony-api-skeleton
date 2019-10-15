<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use App\Util\RequestUtil;
use App\Util\FormatUtil;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/user")
 */
class UserController extends AbstractFOSRestController
{
    public $fields = ['firstName', 'lastName', 'email', 'username', 'phoneNumber', 'gender'];
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/users")
     */
    public function getUsersAction(Request $request) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_READ');
            $query = $request->query;
            $query->set('createdBy', $this->getUser()->getId());
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->filter($query, User::class);
            
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/users/me")
     */
    public function getUserMeAction(Request $request) {
        $user = $this->getUser();
        
        /** @var App\Service\Repository $repository */
        $repository = $this->get('papis.repository');
        $response = $repository->applySerializerContext($user, 'details');
        
        return $this->handleView(FormatUtil::formatView($request, $response));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/users/{id}")
     */
    public function getUserAction(Request $request, EntityManagerInterface $em, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_READ');
            $user = $em->find(User::class, $id);
            
            if ($user->getCreatedBy()->getId() !== $this->getUser()->getId()) {
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->accessDenied($request));
            }
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Post("/users")
     */
    public function postUserAction(Request $request) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_CREATE');
            $data = $request->request->all();
            
            // Data Validation
            $constraint = new Assert\Collection(array(
                'firstName' => new Assert\NotBlank(),
                'lastName' => new Assert\NotBlank(),
                'username' => new Assert\NotBlank(),
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ],
                'password' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 8, 'max' => 4096, 'minMessage' => "user.password.short", 'maxMessage' => "user.password.long"])
                ]
            ));
            
            $validator = Validation::createValidator();
            $violations = $validator->validate($data, $constraint);
            
            if ($violations->count() > 0) {
                throw new BadRequestHttpException((string)$violations);
            }
            
            $user = new User();
            /** @var App\Entity\User $user */
            $user = RequestUtil::matchFields(new User(), $this->fields, $data);
            $user->setPassword(hash('sha512', $data['password']));
            $user->setRoles([User::ROLE_DEFAULT]);
            
            // Entity Validation
            $validator = $this->get('validator');
            if ($violations = $validator->validate($user)) {
                throw new BadRequestHttpException((string)$violations);
            }
            
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            $em->persist($user);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}")
     */
    public function putUserAction(Request $request, $id) {
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager();
        
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_CREATE');
            $data = $request->request->all();
            
            $constraint = new Assert\Collection(array(
                'firstName' => new Assert\NotNull(),
                'lastName' => new Assert\NotNull(),
                'username' => new Assert\NotNull(),
                'phoneNumber' => new Assert\NUll(),
                'gender' => new Assert\Null(),
                'email' => new Assert\Email(),
            ));
            
            $validator = Validation::createValidator();
            $violations = $validator->validate($data, $constraint);
            
            if ($violations->count() > 0) {
                return $this->handleView(FormatUtil::formatView($request, (string)$violations, 400));
            }
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            /** @var App\Entity\User $user */
            $user = RequestUtil::matchFields(new $user, $this->fields, $data);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/enable")
     */
    public function putUserEnableAction(Request $request, EntityManagerInterface $em, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $user->setEnabled(true);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/disable")
     */
    public function putUserDisableAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $user->setEnabled(false);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/lock")
     */
    public function putUserLockAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $user->setLocked(true);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/unlock")
     */
    public function putUserUnlockAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $user->setLocked(false);
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/change-password")
     */
    public function putUserChangePasswordAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            $data = $request->request->all();
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $constraint = new Assert\Collection(array(
                'currentPassword' => new Assert\Length(['min' => 8, 'max' => '4096', 'minMessage' => "user.password.short", 'maxMessage' => "user.password.long"]),
                'password' => new Assert\Length(['min' => 8, 'max' => '4096', 'minMessage' => "user.password.short", 'maxMessage' => "user.password.long"]),
            ));
            
            $validator = Validation::createValidator();
            $violations = $validator->validate($data, $constraint);
            
            if ($violations->count() > 0) {
                return $this->handleView(FormatUtil::formatView($request, (string)$violations, 400));
            }
            
            $password = hash('sha512', $data['password']);
            $currentPassword = hash('sha512', $data['currentPassword']);
            
            if ($user->getPassword() == $currentPassword) {
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->badRequest($request));
            }
            
            $user->setPassword(hash('sha512', $data['password']));
            $user->setPasswordRequestedAt(new \DateTime());
            $user->setPasswordChanged(true);
            
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            $em->flush();
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/add-roles")
     */
    public function putUserAddRolesAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $data = $request->request->all();
            if (isset($data['roles_to_add']) && count($data['roles_to_add']) > 0) {
                $rolesToAdd = $data['roles_to_add'];
                foreach ($rolesToAdd as $role) {
                    $user->addRole($role);
                }
                
                $em = $this->get('doctrine')->getManager();
                $em->flush();
                
                /** @var App\Service\Repository $repository */
                $repository = $this->get('papis.repository');
                $response = $repository->applySerializerContext($user, 'details');
                return $this->handleView(FormatUtil::formatView($request, $response));
            }
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response, 204));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/remove-roles")
     */
    public function putUserRemoveRolesAction(Request $request, $id) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_UPDATE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            $data = $request->request->all();
            if (isset($data['roles_to_remove']) && count($data['roles_to_remove']) > 0) {
                $rolesToRemove = $data['roles_to_remove'];
                foreach ($rolesToRemove as $role) {
                    $user->removeRole($role);
                }
                
                $em = $this->get()->getManager();
                $em->flush();
                
                /** @var App\Service\Repository $repository */
                $repository = $this->get('papis.repository');
                $response = $repository->applySerializerContext($user, 'details');
                return $this->handleView(FormatUtil::formatView($request, $response));
            }
            
            /** @var App\Service\Repository $repository */
            $repository = $this->get('papis.repository');
            $response = $repository->applySerializerContext($user, 'details');
            return $this->handleView(FormatUtil::formatView($request, $response));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Delete("/users/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="App\Entity\User")
     */
    public function deleteUserAction(Request $request, User $user) {
        try{
            $this->denyAccessUnlessGranted('ROLE_API_USER_DELETE');
            /** @var Doctrine\ORM\EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            
            if (! $user = $em->find(User::class, $id)){
                /** @var App\Service\ErrorFactory $errorFactory */
                $errorFactory = $this->get('papis.error_factory');
                return $this->handleView($errorFactory->notFound($request));
            }
            
            $em = $this->get('doctrine')->getManager();
            $em->remove($user);
            $em->flush();
            
            return $this->handleView(FormatUtil::formatView($request, null, 204));
        }catch (AccessDeniedException $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }catch (\Exception $e) {
            /** @var App\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->factory($request, 500));
        }
    }
}

?>