<?php
// https://doc.ezplatform.com/en/3.0/tutorials/extending_admin_ui/1_creating_a_dashboard_tab/

namespace App\Tab\Dashboard\Everyone;

use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use EzSystems\EzPlatformAdminUi\Tab\Dashboard\PagerContentToDataMapper;
use EzSystems\EzPlatformAdminUi\Tab\OrderedTabInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EveryoneLandingPageTab extends AbstractTab implements OrderedTabInterface
{
    /** @var PagerContentToDataMapper */
    protected $pagerContentToDataMapper;

    /** @var SearchService */
    protected $searchService;

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        PagerContentToDataMapper $pagerContentToDataMapper,
        SearchService $searchService
    ) {
        parent::__construct($twig, $translator);

        $this->pagerContentToDataMapper = $pagerContentToDataMapper;
        $this->searchService = $searchService;
    }

    public function getIdentifier(): string
    {
        return 'everyone-landing-page';
    }

    public function getName(): string
    {
        return 'Landing pages';
    }

    public function getOrder(): int
    {
        return 300;
    }

    public function renderView(array $parameters): string
    {
        $page = 1;
        $limit = 10;

        $query = new LocationQuery();

        $query->sortClauses = [new SortClause\DateModified(LocationQuery::SORT_DESC)];
        $query->query = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier('landing_page'),
        ]);

        $pager = new Pagerfanta(
            new ContentSearchAdapter($query,
                $this->searchService
            )
        );
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        return $this->twig->render('@ezdesign/ui/dashboard/tab/all_content.html.twig', [
            'data' => $this->pagerContentToDataMapper->map($pager),
        ]);
    }
}
