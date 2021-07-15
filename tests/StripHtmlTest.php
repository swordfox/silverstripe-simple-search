<?php

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\ClassLoader;
use Arillo\SimpleSearch\SearchIndexEntry;

class StripHtmlTest extends SapphireTest
{
    public function testStripHtml()
    {
        $filename = ClassLoader::inst()->getItemPath(__CLASS__);
        $html = file_get_contents(dirname($filename) . '/data/test.html');
        $ripped = SearchIndexEntry::rip_tags($html);

        $this->assertTrue(strpos($ripped, 'INSIDE_STYLE_TAG') === false);
        $this->assertTrue(strpos($ripped, 'INSIDE_TEMPLATE_TAG') === false);
        $this->assertTrue(strpos($ripped, 'INSIDE_SCRIPT_TAG') === false);
        $this->assertTrue(strpos($ripped, 'INSIDE_SEARCH_EXCLUDE') === false);
    }
}
