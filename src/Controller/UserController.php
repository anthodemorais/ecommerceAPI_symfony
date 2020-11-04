<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;

class UserController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="user", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function index(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $em = $this->getDoctrine()->getManager();
        $user = $em->find(User::class, ['id' => $id]);

        if ($user == null) {
            return $this->json([ 'error' => 'No user for this id' ], 403);
        }

        return $this->json([
            'user' => $user->toArray(),
        ]);
    }

    /**
     * @Route("/user", name="current_user", methods={"GET"})
     */
    public function getCurrentUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if ($user == null) {
            return $this->json([ 'error' => 'No user logged in' ], 403);
        }

        return $this->json([
            'user' => $user->toArray(),
        ]);
    }

    /**
     * @Route("/register", name="create_user", methods={"POST"})
     */
    public function newUser(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $parameters = json_decode($request->getContent(), true);
        $names = ['firstname', 'lastname', 'email', 'password'];
        foreach ($names as $key => $value) {
            if ($parameters[$value] == null) {
                return $this->json([ 'error' => 'Missing '.$value.' field' ], 403);
            }
        }

        $user = new User();
        $user->setFirstname($parameters['firstname']);
        $user->setLastname($parameters['lastname']);
        $user->setEmail($parameters['email']);
        $user->setPassword($passwordEncoder->encodePassword($user, $parameters['password']));
        $user->setRoles(["ROLE_USER"]);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'created_user' => $user->toArray(),
        ]);
    }

    /**
     * @Route("/user", name="update_user", methods={"PUT"})
     */
    public function updateUser(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $parameters = json_decode($request->getContent(), true);

        $user = $this->getUser();
        $names = ['firstname', 'lastname', 'email', 'password'];

        foreach ($names as $key => $value) {
            if ($value == "firstname" && array_key_exists("firstname", $parameters)) {
                $user->setFirstname($parameters['firstname']);
            }
            elseif ($value == "lastname" && array_key_exists("lastname", $parameters)) {
                $user->setLastname($parameters['lastname']);
            }
            elseif ($value == "email" && array_key_exists("email", $parameters)) {
                $user->setEmail($parameters['email']);
            }
            elseif ($value == "password" && array_key_exists("password", $parameters)) {
                $user->setPassword($passwordEncoder->encodePassword($user, $parameters['password']));
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'updated_user' => $user->toArray(),
        ]);
    }

    /**
     * @Route("/user/{id}", name="delete_user", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteUser(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $user = $em->find(User::class, ['id' => $id]);

        if ($user == null) {
            return $this->json([ 'error' => 'No user for this id' ], 403);
        }

        $em->remove($user);
        $em->flush();

        return $this->json([
            'deleted' => "Deleted user with this id",
        ]);
    }

}
