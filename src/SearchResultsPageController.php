<?php
namespace Arillo\SimpleSearch;

use PageController;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Control\HTTPRequest;

class SearchResultsPageController extends PageController
{
    const URLPARAM_SEARCH = 'Search';

    private static $pagination_limit = 12;

    private static $allowed_actions = ['SearchForm'];

    public function index(HTTPRequest $request)
    {
        $searchTerm = $this->request->getVar(self::URLPARAM_SEARCH);

        if (!$searchTerm) {
            return $this->customise([
                'SearchTerm' => null,
                'SearchResultsCount' => 0
            ]);
        }

        $results = SearchIndexEntry::search($searchTerm);

        return $this->customise([
            'SearchTerm' => $searchTerm,
            'SearchResultsCount' => $results->exists() ? $results->count() : 0,
            'SearchResults' => PaginatedList::create(
                $results,
                $this->request
            )->setPageLength($this->config()->pagination_limit)
        ]);
    }

    public function CalculatePos($pos)
    {
        $add = 0;
        if (is_numeric($this->request->getVar('start'))) {
            $add = $this->request->getVar('start');
        }
        return $pos + $add;
    }

    public function SearchForm($name = '', $value = '')
    {
        return SearchForm::create(
            $this,
            $name != '' ? __FUNCTION__ . '-' . $name : __FUNCTION__,
            $value
        )->setFormAction($this->Link());
    }
}
