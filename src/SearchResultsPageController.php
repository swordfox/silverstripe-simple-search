<?php
namespace Arillo\SimpleSearch;

use PageController;
use SilverStripe\ORM\PaginatedList;

class SearchResultsPageController extends PageController
{
    const URLPARAM_SEARCH = 'Search';

    private static $pagination_limit = 12;

    private static $allowed_actions = ['SearchForm'];

    protected function index()
    {
        $searchTerm = $this->request->getVar(self::URLPARAM_SEARCH);

        if (!$searchTerm) {
            return $this;
        }

        return $this->customise([
            'SearchTerm' => $searchTerm,
            'SearchResults' => PaginatedList::create(
                SearchIndexEntry::search($searchTerm),
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
