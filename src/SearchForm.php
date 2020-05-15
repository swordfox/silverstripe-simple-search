<?php
namespace Arillo\SimpleSearch;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FormAction;

class SearchForm extends Form
{
    public function __construct($controller, $name, $value)
    {
        $fields = FieldList::create(
            TextField::create(
                SearchResultsPageController::URLPARAM_SEARCH,
                null
            )
                ->setAttribute(
                    'placeholder',
                    _t(__CLASS__ . '.SearchTerm', 'Enter search term')
                )
                ->setValue($value),
            LiteralField::create(
                'Submit',
                '
                <button class="searchForm_submit" type="submit">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0" y="0" width="20" height="20" viewBox="0 0 20 20" xml:space="preserve"><path d="M13 4a.999.999 0 011.414 0l5.293 5.293a.999.999 0 010 1.414L14.414 16A.999.999 0 1113 14.586L16.585 11H1a1 1 0 01-.993-.882L0 10a1 1 0 011-1h15.585L13 5.415a1 1 0 01-.083-1.32L13 4z"/></svg>
                </button>
            '
            )
        );

        $actions = FieldList::create();

        parent::__construct($controller, $name, $fields, $actions);

        $this->setFormMethod('GET')->disableSecurityToken();
    }

    public function setPlaceholder($placeholder)
    {
        $this->fields
            ->dataFieldByName(SearchResultsPageController::URLPARAM_SEARCH)
            ->setAttribute('placeholder', $placeholder);
        return $this;
    }
}
