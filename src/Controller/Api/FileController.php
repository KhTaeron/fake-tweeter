<?php

namespace App\Controller\Api;

use App\Service\FileService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Fichiers')]
#[OA\Security(name: 'bearerAuth')]
#[Route('/api/files')]
class FileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    #[OA\Post(
        summary: 'Uploader un avatar pour l’utilisateur connecté',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['avatar'],
                        properties: [
                            new OA\Property(
                                property: 'avatar',
                                type: 'string',
                                format: 'binary',
                                description: 'Fichier image à uploader'
                            )
                        ]
                    )
                )
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Fichier uploadé avec succès'),
            new OA\Response(response: 400, description: 'Aucun fichier reçu'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function uploadAvatar(Request $request, FileService $fileService, UserService $userService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $uploadedFile = $request->files->get('avatar');

        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json(['error' => 'Aucun fichier reçu'], 400);
        }

        $file = $fileService->handleUpload($uploadedFile, $user);
        $userService->updateUserAvatar($user, $file);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'fileId' => $file->getId(),
            'filename' => $file->getPath(),
        ]);
    }
}
