<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ElasticSearchService;
use App\Form\SearchFormType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Post;

class SiteController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(ElasticSearchService $elasticSearchService, Request $request)
    {
        $searchForm = $this->createForm(SearchFormType::class);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $formData = $searchForm->getData();
            $searchQuery = $formData['query'] ?? '';
            $posts = $elasticSearchService->getList($searchQuery);
        } else {
            $postRepository = $this->getDoctrine()->getRepository(Post::class);
            $posts = $postRepository->findAll();
        }

        return $this->render('site/index.html.twig', [
            'posts' => $posts,
            'searchForm' => $searchForm->createView()
        ]);
    }
}
