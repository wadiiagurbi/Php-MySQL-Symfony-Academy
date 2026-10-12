<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\TaskCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function __invoke(TaskCategoryRepository $categoryRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $categories = array_map(
            static fn ($category) => ['id' => $category->getId(), 'name' => $category->getName()],
            $categoryRepository->findAllForOwner($user)
        );

        return $this->json(['items' => $categories]);
    }
}
