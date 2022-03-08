<?php
namespace Arillo\SimpleSearch;

use Page;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Extension\FluentExtension;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Core\Convert;

class SearchIndexEntry extends DataObject
{
    const SEPERATOR = ' ';
    const TYPE_PAGE = 'PAGE';
    const TYPE_FILE = 'FILE';

    private static $table_name = 'SearchIndexEntry';
    private static $default_sort = 'LastEdited DESC';

    private static $db = [
        'Title' => 'Text',
        'RecordClass' => 'Varchar(255)',
        'RecordID' => 'Int',
        'SearchableText' => 'Text',
        'Type' => 'Varchar(255)',
    ];

    private static $indexes = [
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['Title','SearchableText'],
        ],
    ];

    private static $filters = [
        'Title:PartialMatch',
        'SearchableText:PartialMatch',
    ];

    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM',
    ];

    public static function rip_tags($string)
    {
        // remove contents between search exclude comments
        $string = preg_replace(
            '/<!--<SearchExclude>-->.*<\/SearchExclude>-->/is',
            ' ',
            $string
        );

        // remove certain tags and their content
        $string = preg_replace('/<form.*?<\/form>/is', ' ', $string);
        $string = preg_replace('/<style.*?<\/style>/is', ' ', $string);
        $string = preg_replace('/<script.*?<\/script>/is', ' ', $string);
        $string = preg_replace('/<template.*?<\/template>/is', ' ', $string);

        // remove html tags
        $string = preg_replace('/<[^>]*>/', ' ', $string);
        // remove html comments
        $string = preg_replace('/<\!--.*?-->/s', '', $string);

        // remove control characters
        $string = str_replace("\r", '', $string);
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\t", ' ', $string);

        // remove multiple spaces
        $string = trim(preg_replace('/ {2,}/', ' ', $string));
        return $string;
    }

    public static function sanitize_string(string $string)
    {
        return preg_replace('/\s+/', self::SEPERATOR, self::rip_tags($string));
    }

    /**
     * @param  DataObject $record
     * @return bool|SearchIndexEntry
     */
    public static function index_record(
        DataObject $record,
        string $string = null,
        string $title = null,
        string $type = self::TYPE_PAGE
    ) {
        if ($record instanceof Page && !$record->ShowInSearch) {
            self::unindex_record($record);
            return false;
        }

        if ($record->hasExtension(Versioned::class)
            && !$record->isPublished()
        ) {
            self::unindex_record($record);
            return false;
        }

        $title = $title ?? $record->Title;

        if (!$string && $record->hasMethod('forSearchIndex')) {
            $string = $record->forSearchIndex();
        }

        if (!$string) {
            self::unindex_record($record);
            return false;
        }

        $index = self::get()
            ->filter(
                [
                'RecordClass' => $record->ClassName,
                'RecordID' => $record->ID,
                ]
            )
            ->first();

        if ($index) {
            if ($index->SearchableText == $string && $index->Title == $title) {
                return $index;
            }

            $index->update(
                [
                'Created' => $record->Created,
                'LastEdited' => $record->LastEdited,
                'Title' => $title,
                'SearchableText' => $string,
                ]
            );

            $index->extend('onIndexRecord', $index, $record);
            $index->write();
            return $index;
        }

        $index = self::create();
        $index->update(
            [
            'Created' => $record->Created,
            'LastEdited' => $record->LastEdited,
            'Title' => $title,
            'RecordClass' => $record->ClassName,
            'RecordID' => $record->ID,
            'SearchableText' => $string,
            'Type' => $type,
            ]
        );

        $index->extend('onIndexRecord', $index, $record);
        $index->write();
        return $index;
    }

    /**
     * @param  DataObject $record
     * @return DataObject
     */
    public static function unindex_record(DataObject $record)
    {
        $index = self::get()
            ->filter(
                [
                'RecordClass' => $record->ClassName,
                'RecordID' => $record->ID,
                ]
            )
            ->first();

        if (!$index) {
            return $record;
        }

        $index->delete();
        return $record;
    }

    /**
     * @param string $query
     * @param string $sort
     * @param string
     *
     * @return DataList
     */
    public static function search(string $keywords, string $sort = null)
    {
        $keywords = Convert::raw2sql(trim($keywords));

        $records = ArrayList::create();

        if (empty($keywords) or strlen($keywords) < 4) {
            return $records;
        }

        $inst = self::singleton();

        $andProcessor = function ($matches) {
            return " +" . $matches[2] . " +" . $matches[4] . " ";
        };

        $notProcessor = function ($matches) {
            return " -" . $matches[3];
        };

        $keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);

        $sql = new SQLSelect();
        $sql->setDistinct(true);
        $sql->setFrom('SearchIndexEntry');
        $sql->addSelect(
            "(
                (2 * (MATCH `Title` AGAINST ('{$keywords}' IN BOOLEAN MODE)))
                +
                (1 * (MATCH `SearchableText` AGAINST ('{$keywords}' IN BOOLEAN MODE)))
            )
            AS Relevance"
        );
        $sql->setWhere(
            "MATCH
                (`Title`, `SearchableText`)
            AGAINST ('{$keywords}' IN BOOLEAN MODE)"
        );

        $sql->setOrderBy("Relevance", "DESC");
        //$rawSQL = $sql->sql();
        //echo $rawSQL;
        $result = $sql->execute();

        foreach ($result as $record) {
            $record = SearchIndexEntry::create($record);
            if($record->getRecord()->canView()) {
                $records->add($record);
            }
        }

        return $records;
    }

    public function getRecord()
    {
        $class = $this->RecordClass;
        if (ClassInfo::exists($class)) {
            return $class::get()->byID($this->RecordID);
        }

        return null;
    }
}
