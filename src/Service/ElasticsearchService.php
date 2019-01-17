<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PostRepository;
use App\Entity\Post;
use FOS\ElasticaBundle\Finder\TransformedFinder;

class ElasticsearchService
{
    /** @var  PostRepository */
    protected $postRepository;

    /** @var TransformedFinder */
    private $transformedFinder;

    public function __construct(EntityManagerInterface $em, TransformedFinder $transformedFinder)
    {
        $this->postRepository = $em->getRepository(Post::class);
        $this->transformedFinder = $transformedFinder;
    }

    public function getList(string $query)
    {
        $query = $this->buildQuery($query);
        $results = $this->transformedFinder->find($query);

        return $results;
    }

    protected function buildQuery(string $query)
    {
        $textQuery = new \Elastica\Query\MultiMatch();
        $textQuery->setQuery($query);
        $textQuery->setFields(['title', 'content']);

        $statusQuery = new \Elastica\Query\Match();
        $statusQuery->setFieldQuery('status', Post::STATUS_ACTIVE);

        $result = new \Elastica\Query\BoolQuery();
        $result->addMust($textQuery);
        $result->addMust($statusQuery);

        return $result;
    }
}
