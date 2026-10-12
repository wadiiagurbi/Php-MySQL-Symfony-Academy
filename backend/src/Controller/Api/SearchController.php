<?php

namespace App\Controller\Api;

use App\Service\CourseContentLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function __invoke(Request $request, CourseContentLoader $loader): JsonResponse
    {
        $query = (string) $request->query->get('q', '');

        if ('' === trim($query)) {
            return $this->json(['query' => $query, 'results' => []]);
        }

        $matches = $loader->search($query);

        $results = array_map(static fn (array $match) => [
            'lesson' => $match['lesson'],
            'excerpt' => $match['excerpt'],
        ], $matches);

        return $this->json(['query' => $query, 'results' => $results]);
    }
}
