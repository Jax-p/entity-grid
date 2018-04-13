<?php

/**
 * Simple BootstrapFormRenderer used for form rendering with Bootstrap classes
 * @author: Jakub Patocka, 2018
 * Inspired by original BS3 Nette renderer
 * @see https://github.com/nette/forms/blob/a0bc775b96b30780270bdec06396ca985168f11a/examples/bootstrap3-rendering.php
 */

namespace Jax_p\EntityGrid;

/**
 * Class BootstrapFormRenderer
 * @package Jax_p\EntityGrid
 */
class BootstrapFormRenderer
{
    use \Nette\SmartObject;
        
    /**
     * @param \Nette\Application\UI\Form $form
     * @return \Nette\Application\UI\Form
     */
    public static function render($form)
    {
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL; // Obalení

        $renderer->wrappers['pair']['container'] = 'div class=form-group'; // Obalení každého Inputu + Labelu
        $renderer->wrappers['pair']['.error'] = 'has-error'; // Má li chybu přidá classu

        $renderer->wrappers['error']['container'] = 'div class=error'; // Obal chyby
        $renderer->wrappers['error']['item'] = 'div class="alert alert-danger"'; // Blok chyby - alert

        $renderer->wrappers['control']['container'] = 'div class=col-sm-6'; // Kde je input
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"'; // Kde je label

        $class = $form->getElementPrototype()->class;
        $form->getElementPrototype()->class('form-horizontal'.(strlen($class)?' '.$class:''));

        $first_button = false;
        foreach ($form->getControls() as $control) {
            if ($control instanceof \Nette\Forms\Controls\Button) {
                $control->getControlPrototype()->addClass($first_button ? 'btn btn-primary' : 'btn btn-default');
                $first_button = TRUE;
            } elseif ($control instanceof \Nette\Forms\Controls\TextBase || $control instanceof \Nette\Forms\Controls\SelectBox || $control instanceof \Nette\Forms\Controls\MultiSelectBox) {
                $control->getControlPrototype()->addClass('form-control');
            } elseif ($control instanceof \Nette\Forms\Controls\Checkbox || $control instanceof \Nette\Forms\Controls\CheckboxList || $control instanceof \Nette\Forms\Controls\RadioList) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }

        return $form;
    }

}
