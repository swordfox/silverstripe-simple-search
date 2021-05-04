<?php
namespace Arillo\SimpleSearch;

use SilverStripe\ORM\DataExtension;

class ElementDataExtension extends DataExtension
{
    public function rootElementPublished($rootElement)
    {
        if (
            $rootElement &&
            $rootElement->hasMethod('getHolderPage') &&
            ($holder = $rootElement->getHolderPage())
        ) {
            SearchIndexEntry::index_record($holder);
        }
    }

    public function onAfterPublish($original)
    {
        if (
            $this->owner->hasMethod('getHolderPage') &&
            ($holder = $this->owner->getHolderPage())
        ) {
            SearchIndexEntry::index_record($holder);
        }
    }
}
