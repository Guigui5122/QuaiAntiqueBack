<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        )
    {
    }
    
    #[Route('/registration', name: 'registration', methods: 'POST')]
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
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        //si le user n'est pas créé, retourne une erreur 
        if (null === $user){
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

    // TODO : implémenter la méthode me() 
    /**
     * Une fonction me() dans le contrôleur Security retournant l’objet $user sérialisé,
     */
    // TODO : implémenter la méthode edit() 
    /**
     * Une fonction edit() dans le même contrôleur désérialisant l’objet Request $request,
     * mettant à jour les informations de l’utilisateur (dont le mot de passe et la date de mise à jour de l’objet UpdatedAt) et les flushant en base.
     */


}
