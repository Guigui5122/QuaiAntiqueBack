<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private RestaurantRepository $repository)
     {

     }

    // CRUD restaurant 
    #[Route('/new', name: 'new', methods: 'POST')]
    public function new(): Response
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Quai Antique');
        $restaurant->setDescription('Cette qualité et ce goût par le chef Arnaud MICHANT.');
        $restaurant->setCreatedAt(new DateTimeImmutable());

        // A stocker en base de donnée
        $this->manager->persist($restaurant); // file d'attente
        $this->manager->flush(); // envoi, pousse les données en bdd

        return $this->json(
            ['message' => "Restaurant resource created with {$restaurant->getId()} id"],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/{$id}', name: 'show', methods: 'GET')]
    public function show(int $id): Response
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }
        return $this->json(
            ['message' => "A restaurant was found : {$restaurant->getName()} for {$restaurant->getId()} id"]
        );
    }


    #[Route('/{$id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id): Response
    {

        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        $restaurant->setName('Restaurant name updated');
        $this->manager->flush();

        return $this->redirectToRoute('app_api_restaurant_show', ['id' => $restaurant->getId()]);
    }


    #[Route('/{$id}', name: 'delete', methods: 'DELETE')]
    public function delete(): Response
    {
        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        return $this->json(['message' => 'Restaurant resource deleted!'], Response::HTTP_NO_CONTENT);
    }
}
