<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiController extends AbstractController
{
    #[Route('/get', name: 'get_all', methods: ['GET'])]
    public function get(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAll();

        $datas = [];
        foreach ($tasks as $task) {
            $datas[] = [
                "id" => $task->getId(),
                "title" => $task->getTitle(),
                "description" => $task->getDescription(),
                "status" => $task->isStatus(),
                "created_at" => $task->getCreatedAt(),
            ];
        }

        return new JsonResponse($datas, Response::HTTP_OK);
    }

    #[Route('/get/{id}', name: 'get_unique', methods: ['GET'])]
    public function getUnique(TaskRepository $taskRepository, int $id): Response
    {
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(['message' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        };

        $data = [
            "id" => $task->getId(),
            "title" => $task->getTitle(),
            "description" => $task->getDescription(),
            "status" => $task->isStatus(),
            "created_at" => $task->getCreatedAt(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/post', name: 'post', methods: ['POST'])]
    public function post(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'];
        $description = $data['description'];
        $status = $data['status'];

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setStatus($status);

        $entityManager->persist($task);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Tâche créée'], Response::HTTP_CREATED);
    }

    #[Route('/patch/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(Request $request, EntityManagerInterface $entityManager, int $id, TaskRepository $taskRepository): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['message' => 'Tâche introuvable']);
        }

        $data = json_decode($request->getContent(), true);

        $fields = ['title', 'description', 'status'];

        $isModified = false;
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($task, $setter)) {
                    $task->$setter($data[$field]);
                    $isModified = true;
                }
            }
        }

        if (!$isModified) {
            return new JsonResponse(['message' => 'Aucun champ valide à modifier'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($task);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Tâche modifiée', Response::HTTP_NO_CONTENT]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, TaskRepository $taskRepository): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['message' => 'Tâche introuvable']);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Tâche supprimée'], Response::HTTP_NO_CONTENT);
    }
}
