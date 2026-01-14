<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserRepository $repository
    ) {}


    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[OA\Post(
        path: '/api/registration',
        summary: 'Inscription d\'un nouvel utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de l\'utilisateur à inscrire',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'Votre prénom'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Votre nom'),
                    new OA\Property(property: 'password', type: 'string', example: 'Mot de passe')
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'Nom d\'utilisateur'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        )
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();
        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED
        );
    }


    #[Route('/login', name: 'login', methods: 'POST')]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connecter un utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de l\'utilisateur pour se connecter',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'adresse@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Mot de passe')
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'Nom d\'utilisateur'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        )
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        //si le user n'est pas créé, retourne une erreur 
        if (null === $user) {
            return new JsonResponse([
                'message' => 'L\'utilisateur n\'existe pas, veuillez créer un compte',
                Response::HTTP_UNAUTHORIZED
            ]);
        }
        //sinon on affiche les infos de l'utilisateur et un message
        return new JsonResponse([
            'message' => 'Bienvenue sur votre compte!',
            'path' => 'src/Controller/SecurityController.php',
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles()
        ]);
    }

    /**
     * Une fonction me() dans le contrôleur Security retournant l’objet $user sérialisé,
     */
    #[Route('/user/{id}', name: 'user', methods: 'GET')]
    #[OA\Get(
        path: '/user/{id}',
        summary: 'Voir un utilisateur par son id',
        tags: ['User']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID de l\'utilisateur à afficher',
        schema: new OA\Schema(type: 'integer')
    )]
    //réponse si l'ID existe
    #[OA\Response(
        response: 200,
        description: 'Utilisateur trouvé avec succès',
        // Affiche ce contenu :
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    // réponse en cas d'ID introuvable
    #[OA\Response(response: 404, description: 'Utilisateur non trouvé')]
    public function me(int $id): JsonResponse
    {
        $user = $this->repository->findOneBy(['id' => $id]);

        if ($user) {
            $responseData = $this->serializer->serialize($user, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    // TODO : implémenter la méthode edit() 
    /**
     * Une fonction edit() dans le même contrôleur désérialisant l’objet Request $request,
     * mettant à jour les informations de l’utilisateur (dont le mot de passe et la date de mise à jour de l’objet UpdatedAt) et les flushant en base.
     */
// Modifier un utilisateur
    #[Route('/editAccount/{id}', name: 'edit', methods: 'PUT', requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/api/editAccount/{id}',
        summary: 'Modifier un utilisateur par son id',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Informations de l\'utilisateur à modifier',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'password', type: 'string', example: 'Mon mot de passe'),
                    new OA\Property(property: 'updatedAt', type: 'string', example: 'date de mise a jour')
                ],
                type: 'object'
            )
        ),
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de l\utilisateur à modifier',
        schema: new OA\Schema(type: 'integer')
    )]
    //réponse si l'ID existe
    #[OA\Response(
        response: 200,
        description: 'Utilisateur modifié avec succès',
        // Affiche ce contenu :
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    // réponse en cas d'ID introuvable
    #[OA\Response(response: 404, description: 'Utilisateur non trouvé', )]
    public function editAccount(int $id, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
    $user = $this->repository->findOneBy(['id' => $id]);

    if ($user) {
        $data = json_decode($request->getContent(), true);

        // Si un nouveau mot de passe est fourni, on le hash
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        // Désérialisation du reste des données (sans le mot de passe)
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['password'] // On ignore le password pour éviter l'écrasement
            ]
        );

        $user->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
}



}
