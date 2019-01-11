<?php

namespace App\Service;

use App\Service\BaseService;
use Doctrine\ORM\EntityManagerInterface;

use App\Constant\{
    Post as PostConstant,
    Blog
};
use Elasticsearch\{
    ClientBuilder,
    Client
};
use App\Repository\{
    PostRepository
};
use App\Entity\{
    Post
};

class ElasticSearchService implements PaginatedListInterface, SearchServiceInterface
{
    /** @var  PostRepository */
    protected $postRepository;

    /** @var PaginatorInterface */
    protected $paginator;

    /** @var Client */
    private $client;

    private $searchResponseDump = [];

    public function __construct(EntityManagerInterface $em, PaginatorInterface $paginator)
    {
        $this->postRepository = $em->getRepository(Post::class);
        $this->paginator = $paginator;
        $this->setClient();
    }

    protected function setClient()
    {
        $hosts = [
            'elasticsearch:9200',
        ];
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function getPaginatedList($query, $page) : PaginationInterface
    {
        $query = $this->getSearchQuery($query);
        $results = $this->client->search($query);
        $this->setResponseDump($results);
        $queryBuilder = $this->getQueryBuilder($results);

        return $this->paginator->paginate($queryBuilder, $page, Blog::ITEMS_PER_PAGE);
    }

    protected function getQueryBuilder($results)
    {
        $postIds = $this->getPostIdsFromSearchResult($results);
        $queryBuilder = [];
        if (!empty($postIds)) {
            $queryBuilder = $this->postRepository->getPostsByIdsQueryBuilder($postIds);
        }

        return $queryBuilder;
    }

    protected function getSearchQuery($query, $order = 'desc')
    {
        $result = [
            'body' => [
                'sort' => [
                    'created' => [
                        'order' => $order
                    ]
                ],
                'query' => [
                    'match' => [
                        'content' => $query
                    ]
                ],
            ]
        ];

        return $result;
    }

    protected function getPostIdsFromSearchResult($searchResult)
    {
        $ids = [];
        $searchResult = $searchResult['hits']['hits'] ?? [];
        if (empty($searchResult) && !is_array($searchResult)) {
            return;
        }
        foreach ($searchResult as $item) {
            $ids[] = $item['_id'];
        }

        return $ids;
    }

    protected function getAlternateQuery($query)
    {
        $query = $this->getStringQuery($query);
        $query = $this->getArrayQuery($query);

        return $query;
    }

    protected function getArrayQuery($query, $status = PostConstant::STATUS_PUBLISHED)
    {
        $result = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'content' => $query
                        ],
                    ],
                    'filter' => [
                        'term' => [
                            'status' => $status
                        ]
                    ],
                ]
            ]
        ];

        return $result;
    }

    protected function getStringQuery($query)
    {
        return $query;
    }

    public function setResponseDump($dump)
    {
        $this->searchResponseDump = $dump;
    }

    public function getResponseDump()
    {
        return $this->searchResponseDump;
    }
}
