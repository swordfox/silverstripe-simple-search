<?php
namespace Arillo\SimpleSearch;

use Page;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Extension\FluentExtension;
use SilverStripe\Versioned\Versioned;

class SearchIndexEntry extends DataObject
{
    const SEPERATOR = ' ';
    const TYPE_PAGE = 'PAGE';
    const TYPE_FILE = 'FILE';

    private static $table_name = 'Arillo_SimpleSearch_SearchIndexEntry';
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
            'columns' => ['SearchableText'],
        ],
    ];

    private static $filters = [
        'Title:PartialMatch',
        'SearchableText:PartialMatch',
    ];

    public static function rip_tags($string)
    {
        // remove certain tags and their content
        $string = preg_replace('/<form.*?<\/form>/is', ' ', $string);
        $string = preg_replace('/<style.*?<\/style>/is', ' ', $string);
        $string = preg_replace('/<script.*?<\/script>/is', ' ', $string);
        $string = preg_replace('/<template.*?<\/template>/is', ' ', $string);

        // remove html tags
        $string = preg_replace('/<[^>]*>/', ' ', $string);

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
     * @param DataObject $record
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

        if (
            $record->hasExtension(Versioned::class) &&
            !$record->isPublished()
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
            ->filter([
                'RecordClass' => $record->ClassName,
                'RecordID' => $record->ID,
            ])
            ->first();

        if ($index) {
            if ($index->SearchableText == $string && $index->Title == $title) {
                return $index;
            }

            $index->update([
                'Created' => $record->Created,
                'LastEdited' => $record->LastEdited,
                'Title' => $title,
                'SearchableText' => $string,
            ]);

            $index->write();
            return $index;
        }

        $index = self::create();
        $index->update([
            'Created' => $record->Created,
            'LastEdited' => $record->LastEdited,
            'Title' => $title,
            'RecordClass' => $record->ClassName,
            'RecordID' => $record->ID,
            'SearchableText' => $string,
            'Type' => $type,
        ]);

        $index->write();
        return $index;
    }

    /**
     * @param DataObject $record
     * @return DataObject
     */
    public static function unindex_record(DataObject $record)
    {
        $index = self::get()
            ->filter([
                'RecordClass' => $record->ClassName,
                'RecordID' => $record->ID,
            ])
            ->first();

        if (!$index) {
            return $record;
        }

        $index->delete();
        return $record;
    }

    /**
     * @param  string   $query
     * @param string    $sort
     * @param string
     *
     * @return DataList
     */
    public static function search(string $query, string $sort = null)
    {
        $query = explode(' ', $query);
        $query = array_map('strtolower', $query);

        $inst = self::singleton();
        $filterConfig = $inst->config()->filters;
        $filters = [];

        foreach ($filterConfig as $key) {
            $filters[$key] = $query;
        }

        $records = SearchIndexEntry::get()->filterAny($filters);
        return $records->sort($sort ?? $inst->default_sort);
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
