<?php

namespace Arillo\SimpleSearch;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLDelete;
use TractorCow\Fluent\Extension\FluentVersionedExtension;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\State\FluentState;
use TractorCow\Fluent\Model\Locale;

/**
 * @package Reg
 * @subpackage Search
 * @author <bumbus sf@arillo.ch>
 */
class BuildIndexTask extends BuildTask
{
    protected $title = '(Re-)build search index';
    protected $description = 'Only needs to be called once on production, or to re-build the manifest';

    public function run($request)
    {
        set_time_limit(0);
        SQLDelete::create()
            ->setFrom(SearchIndexEntry::singleton()->config()->table_name)
            ->execute();

        // remove localised table if exists
        try {
            SQLDelete::create()
                ->setFrom(
                    SearchIndexEntry::singleton()->config()->table_name .
                        '_Localised'
                )
                ->execute();
        } catch (\Throwable $th) {
            $this->outputText($th->getMessage());
        }

        $classes = array_values(
            ClassInfo::implementorsOf(ISearchIndexable::class)
        );

        if (empty($classes)) {
            $this->outputText(
                'No models implement ISearchIndexable. Nothing to do...'
            );
            exit();
        }

        foreach ($classes as $class) {
            $this->runOnClass($class);
        }

        $this->outputText('-> COMPLETED');
    }

    public function runOnClass(string $className)
    {
        $instance = $className::singleton();
        $this->outputText("-> Indexing: {$className}");
        $count = 0;

        if (
            $instance->hasExtension(FluentVersionedExtension::class) ||
            $instance->hasExtension(FluentExtension::class)
        ) {
            $state = new FluentState();
            $self = $this;
            foreach (Locale::get() as $locale) {
                $state->setLocale($locale->Locale);
                $count += $state->withState(function ($state) use (
                    $self,
                    $className
                ) {
                    return $self->runOnRecords($records = $className::get());
                });
            }
        } else {
            $count = $this->runOnRecords($records = $className::get());
        }

        $this->outputText("-> Indexing done [{$count}]");
    }

    public function runOnRecords($records)
    {
        if (!$records->exists()) {
            $this->outputText("->'{$className}' has nothing to index...");
            return 0;
        }

        foreach ($records as $record) {
            SearchIndexEntry::index_record($record, $record->forSearchIndex());
            $this->outputText(
                "-> Indexed: {$record->Title} [{$record->Locale}]"
            );
        }
        return $records->count();
    }

    public function outputText(string $text, $break = true)
    {
        if ($break) {
            $break = '<br>';

            if (Director::is_cli()) {
                $break = PHP_EOL;
            }

            echo $text . $break;
            return;
        }

        echo $text;
    }
}
