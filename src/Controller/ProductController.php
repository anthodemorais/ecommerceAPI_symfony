<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Product;
use Knp\Component\Pager\PaginatorInterface;

class ProductController extends AbstractController
{

    /**
     * @Route("/products", name="products", methods={"GET"})
     */
    public function getProducts(Request $request, PaginatorInterface $paginator): Response
    {
        $data = $this->getDoctrine()->getRepository(Product::class)->findAll();

        $parameters = json_decode($request->getContent(), true);
        $page = 1;

        if (array_key_exists("page", $parameters)) {
            $page = $parameters["page"];
        }

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            50
        );

        $result = array();
        foreach ($products as $key => $product) {
            array_push($result, $product->toArray());
        }

        return $this->json([
            'products' => $result,
        ]);
    }

    /**
     * @Route("/product/{id}", name="product", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getProduct(Request $request, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $product = $em->find(Product::class, ['id' => $id]);

        if ($product == null) {
            return $this->json([ 'error' => 'No product for this id' ], 403);
        }

        return $this->json([
            'product' => $product->toArray(),
        ]);
    }

    /**
     * @Route("/product", name="create_product", methods={"POST"})
     */
    public function newProduct(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $parameters = json_decode($request->getContent(), true);
        $names = ['name', 'picture', 'description', 'price', 'quantity'];
        foreach ($names as $key => $value) {
            if ($parameters[$value] == null) {
                return $this->json([ 'error' => 'Missing '.$value.' field' ], 403);
            }
        }

        $product = new Product();
        $product->setName($parameters['name']);
        $product->setPicture($parameters['picture']);
        $product->setDescription($parameters['description']);
        $product->setPrice($parameters['price']);
        $product->setQuantity($parameters['quantity']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        return $this->json([
            'created_product' => $product->toArray(),
        ]);
    }

    /**
     * @Route("/product/{id}", name="update_product", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function updateProduct(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $parameters = json_decode($request->getContent(), true);

        $em = $this->getDoctrine()->getManager();
        $product = $em->find(Product::class, ['id' => $id]);

        if ($product == null) {
            return $this->json([ 'error' => 'No product for this id' ], 403);
        }

        $names = ['name', 'picture', 'description', 'price', 'quantity'];

        foreach ($names as $key => $value) {
            if ($value == "name" && array_key_exists("name", $parameters)) {
                $product->setName($parameters['name']);
            }
            elseif ($value == "picture" && array_key_exists("picture", $parameters)) {
                $product->setPicture($parameters['picture']);
            }
            elseif ($value == "description" && array_key_exists("description", $parameters)) {
                $product->setDescription($parameters['description']);
            }
            elseif ($value == "price" && array_key_exists("price", $parameters)) {
                $product->setPrice($parameters['price']);
            }
            elseif ($value == "quantity" && array_key_exists("quantity", $parameters)) {
                $product->setQuantity($parameters['quantity']);
            }
        }

        $em->persist($product);
        $em->flush();

        return $this->json([
            'updated_product' => $product->toArray(),
        ]);
    }

    /**
     * @Route("/product/{id}", name="delete_product", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteProduct(Request $request, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $product = $em->find(Product::class, ['id' => $id]);

        if ($product == null) {
            return $this->json([ 'error' => 'No product for this id' ], 403);
        }

        $em->remove($product);
        $em->flush();

        return $this->json([
            'deleted' => "Deleted product with this id",
        ]);
    }
}
