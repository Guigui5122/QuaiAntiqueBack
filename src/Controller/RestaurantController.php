<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    // Créer un restaurant
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Quai Antique');
        $restaurant->setDescription('Cette qualité et ce goût par le chef Arnaud MICHANT.');
        $restaurant->setCreatedAt(new DateTimeImmutable());
        $restaurant->setAmOpeningTime(['09:00']);
        $restaurant->setMaxGuest(40);
        $restaurant->setOwner(1);


        // A stocker en base de donnée
        $this->manager->persist($restaurant); // file d'attente pour les nouveaux objets uniquement (pas besoin pour 'edit')
        $this->manager->flush(); // envoi, pousse les données en bdd

        return $this->json(
            ['message' => "Restaurant resource created with {$restaurant->getId()} id"],
            Response::HTTP_CREATED,
        );
    }

    // Voir les restaurants 
    #[Route('/{id}', name: 'show', methods: 'GET', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw new \Exception("No Restaurant found for {$id} id");
        }
        return $this->json(
            ['message' => "A restaurant was found : {$restaurant->getName()} for {$restaurant->getId()} id"]
        );
    }

    // Modifier un restaurant
    #[Route('/{id}', name: 'edit', methods: 'PUT', requirements: ['id' => '\d+'])]
    public function edit(int $id): Response
    {

        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw new \Exception("No Restaurant found for {$id} id");
        }

        $restaurant->setName('Restaurant name updated');
        $this->manager->flush();

        return $this->redirectToRoute('app_api_restaurant_show', ['id' => $restaurant->getId()]);
    }

    // Supprimer un restaurant
    #[Route('/{id}', name: 'delete', methods: 'DELETE', requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if (!$restaurant) {
            throw new Exception("No Restaurant found for {$id} id");
        }
        
        $this->manager->remove($restaurant);
        $this->manager->flush();

        return $this->json(['message' => 'Restaurant resource deleted!'], Response::HTTP_NO_CONTENT);
    }
}
