<?php

namespace Arillo\SimpleSearch;

use SilverStripe\ORM\DataExtension;

class SearchableExtension extends DataExtension
{
    public function onAfterWrite()
    {
        SearchIndexEntry::index_record($this->owner);
    }

    public function onAfterVersionedPublish()
    {
        SearchIndexEntry::index_record($this->owner);
    }

    public function onAfterUnpublish()
    {
        SearchIndexEntry::unindex_record($this->owner);
    }

    public function onAfterPublish()
    {
        SearchIndexEntry::index_record($this->owner);
    }

    public function onBeforeDelete()
    {
        SearchIndexEntry::unindex_record($this->owner);
    }
}
