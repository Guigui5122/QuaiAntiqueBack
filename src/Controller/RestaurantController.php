<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    // CRUD restaurant 
    // Créer un restaurant
    #[Route(name: 'new', methods: 'POST')]
    #[OA\Post(
        path: '/api/restaurant',
        summary: 'Créer un restaurant ',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Saisir les informations du restaurant',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mon Restaurant'),
                    new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...')
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Restaurant enregistré avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Mon restaurant'),
                        new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...'),
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'createdAt', type: 'string', format: "date-time")
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        //sérialiser 
        $restaurant = $this->serializer->deserialize($request->getContent(), Restaurant::class, 'json');
        $restaurant->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($restaurant); // file d'attente pour les nouveaux objets uniquement (pas besoin pour 'edit')
        $this->manager->flush(); // envoi, pousse les données en bdd

        return new JsonResponse(null, Response::HTTP_CREATED, []);

        $responseData = $this->serializer->serialize($restaurant, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ["location" => $location]);
    }



    // Voir les restaurants 
    #[Route('/{id}', name: 'show', methods: 'GET', requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/restaurant/{id}',
        summary: 'Voir un restaurant par son id'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du restaurant à afficher',
        schema: new OA\Schema(type: 'integer')
    )]
    //réponse si l'ID existe
    #[OA\Response(
        response: 200,
        description: 'Restaurant trouvé avec succès',
        // Affiche ce contenu :
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Mon restaurant'),
                new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    // réponse en cas d'ID introuvable
    #[OA\Response(
        response: 404,
        description: 'Restaurant non trouvé',
    )]
    public function show(int $id): Response
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if ($restaurant) {
            $responseData = $this->serializer->serialize($restaurant, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }



    // Modifier un restaurant
    #[Route('/{id}', name: 'edit', methods: 'PUT', requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/api/restaurant/{id}',
        summary: 'Modifier un restaurant par son id',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Informations du restaurant à modifier',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mon Restaurant'),
                    new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...'),
                    new OA\Property(property: 'maxGuest', type: 'integer', example:35)
                ],
                type: 'object'
            )
        ),
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du restaurant à modifier',
        schema: new OA\Schema(type: 'integer')
    )]
    //réponse si l'ID existe
    #[OA\Response(
        response: 200,
        description: 'Restaurant modifié avec succès',
        // Affiche ce contenu :
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Mon restaurant'),
                new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'maxGuest', type: 'integer', example: 44),
                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    // réponse en cas d'ID introuvable
    #[OA\Response(
        response: 404,
        description: 'Restaurant non trouvé',
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if ($restaurant) {
            $restaurant = $this->serializer->deserialize(
                $request->getContent(),
                Restaurant::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]
            );

            $restaurant->setUpdatedAt(new DateTimeImmutable());
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }



    // Supprimer un restaurant
    #[Route('/{id}', name: 'delete', methods: 'DELETE', requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: '/api/restaurant/{id}',
        summary: 'Supprimer un restaurant par son id'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du restaurant à supprimer',
        schema: new OA\Schema(type: 'integer')
    )]
    //réponse si l'ID existe
    #[OA\Response(
        response: 200,
        description: 'Restaurant supprimé avec succès',
        // Affiche ce contenu :
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Mon restaurant'),
                new OA\Property(property: 'description', type: 'string', example: 'C\'est un super restaurant...'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if ($restaurant) {
            $this->manager->remove($restaurant);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
