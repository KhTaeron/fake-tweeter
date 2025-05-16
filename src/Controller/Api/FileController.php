<?php
namespace App\Controller\Api;

use App\Service\FileService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/files')]
class FileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, FileService $fileService, UserService $userService): JsonResponse
    {
        $user = $this->getUser();

        $uploadedFile = $request->files->get('avatar');

        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json(['error' => 'Aucun fichier reçu'], 400);
        }

        $file = $fileService->handleUpload($uploadedFile, $user);
        $userService->updateUserAvatar($user,$file);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'fileId' => $file->getId(),
            'filename' => $file->getPath(),
        ]);
    }

}