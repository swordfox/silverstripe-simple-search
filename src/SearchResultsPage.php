<?php
namespace Arillo\SimpleSearch;

use Page;

class SearchResultsPage extends Page
{
    private static $singular_name = 'Search';
    private static $pluaral_name = 'Searchs';
    private static $description = 'Search results page';
    private static $plural_name = 'Search pages';
    private static $icon_class = 'font-icon-search';

    private static $default = [
        'ShowInSearch' => false,
        'ShowInMenues' => false
    ];

    public function getCMSFields()
    {
        return parent::getCMSFields()->removeByName('Content');
    }
}
