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
                ->setTitle(null)
                ->setAttribute(
                    'placeholder',
                    _t(__CLASS__ . '.SearchTerm', 'Enter search term')
                )
                ->setValue($value),
            LiteralField::create(
                'Submit',
                $this->renderWith(__CLASS__ . 'Button')
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
