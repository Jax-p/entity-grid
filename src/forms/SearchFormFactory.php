<?php

/**
 * SearchFormFactory for entities filter
 * @author Jakub Patocka, 2018
 */

namespace Jax_p\EntityGrid;

/**
 * Class SearchFormFactory
 * @package Jax_p\EntityGrid
 */
final class SearchFormFactory
{

    /**
     * @param $inputs
     * @param callable $onSuccess
     * @param callable $onCancel
     * @return \Nette\Application\UI\Form
     */
    public function create($inputs, callable $onSuccess, callable $onCancel) {
        $form = new \Nette\Application\UI\Form();
        $form->getElementPrototype()->setAttribute('class','ajax');

        foreach ($inputs as $name => $option) {
            switch ($option['input']) {
                case 'text':
                    $form->addText($name);
                    break;
                case 'integer':
                    $form->addInteger($name);
                    break;
                case 'select':
                    $form->addSelect($name, $name, $option['select'])->setPrompt($name);
                    break;
                case 'checkbox':
                    $form->addSelect($name, $name, [true => 'ano', false => 'ne'])->setPrompt($name);
                    break;
                case 'date':
                    $form->addText($name)->setType('date');
                    $form->addText($name.'_to')->setType('date');
                    break;
            }
        }

        $form->addSubmit('search')->getControlPrototype()->setName('button')->setHtml('<i class="fa fa-search" title="hledat"></i>');
        $form->addSubmit('cancel')->getControlPrototype()->setName('button')->setHtml('<i class="fa fa-times" title="reset"></i>')->setValidationScope(FALSE);

        $form->onSuccess[] = function (\Nette\Application\UI\Form $form, $values) use ($onSuccess, $onCancel) {
            $form['cancel']->isSubmittedBy() ? $onCancel($values, $form) : $onSuccess($values);
        };

        return $form;

    }

}