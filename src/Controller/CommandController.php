<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Command;
use App\Entity\Product;

class CommandController extends AbstractController
{
    /**
     * @Route("/command", name="create_command", methods={"POST"})
     */
    public function newCommand(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $parameters = json_decode($request->getContent(), true);

        $names = ['address', 'zipcode', 'city', 'price', 'products'];
        foreach ($names as $key => $value) {
            if ($parameters[$value] == null) {
                return $this->json([ 'error' => 'Missing '.$value.' field' ], 403);
            }
        }

        $user = $this->getUser();

        $command = new Command();
        $command->setAddress($parameters["address"]);
        $command->setZipcode($parameters["zipcode"]);
        $command->setCity($parameters["city"]);
        $command->setPrice($parameters["price"]);
        $command->setStatus("payé");
        $command->setUser($user);
        $command->setCreatedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();

        foreach ($parameters["products"] as $key => $id) {
            $product = $em->find(Product::class, ['id' => $id]);

            if ($product == null) {
                return $this->json([ 'error' => 'No product for the id '.$id ], 403);
            }

            $command->addProduct($product);
        }

        $em->persist($command);
        $em->flush();

        return $this->json([
            'created_command' => $command->toArray(),
        ]);
    }

    /**
     * @Route("/command", name="my_command", methods={"GET"})
     */
    public function myCommands(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager()->getRepository(Command::class);
        $commands = $em->findBy(['user' => $user, 'status' => ["payé", "en préparation", "en transit", "livré"]]);

        $result = array();
        foreach ($commands as $key => $command) {
            array_push($result, $command->toArray());
        }

        return $this->json([
            'command' => $result,
        ]);
    }

    /**
     * @Route("/command/cancel/{id}", name="cancel_command", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function cancelCommand(Request $request, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $command = $em->find(Command::class, ['id' => $id]);

        if ($command == null) {
            return $this->json([ 'error' => 'No command for this id :'.$command->getId() ], 403);
        }

        if ($command->getStatus() == "payé") {
            $command->setStatus("annulé");
            $em->persist($command);
            $em->flush();

            return $this->json([
                'canceled' => $command->toArray(),
            ]);
        }
        else {
            return $this->json([
                'error' => "Can't cancel command".$command->getId()."bacause status is not 'payé'",
            ], 403);
        }
    }

}
