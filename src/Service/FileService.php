<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileService
{
    public function __construct(
        private string $avatarsDir,
        private EntityManagerInterface $em
    ) {
    }

    public function handleUpload(UploadedFile $file, User $user): File
    {
        $filename = uniqid() . '.' . $file->guessExtension();

        // 🔁 Récupère les infos avant de déplacer le fichier
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        $file->move($this->avatarsDir, $filename); // Ce move supprime le fichier temporaire

        // 💾 Crée et enregistre l'entité
        $entity = new File();
        $entity->setPath($filename);
        $entity->setOriginalName($originalName);
        $entity->setMimeType($mimeType);
        $entity->setSize($size);
        $entity->setUploadedAt(new \DateTimeImmutable());

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

}
