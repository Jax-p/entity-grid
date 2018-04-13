<?php

/**
 * DetailFormFactory for entity update
 * @author Jakub Patocka, 2018
 */

namespace Jax_p\EntityGrid;

/**
 * Class DetailFormFactory
 * @package Jax_p\EntityGrid
 */
final class DetailFormFactory
{

    /**
     * @param $inputs
     * @param $model
     * @param callable $onSuccess
     * @param callable $onCancel
     * @param callable $onFailed
     * @return \Nette\Application\UI\Form
     */
    public function create($inputs, $model, callable $onSuccess, callable $onCancel, callable $onFailed) {
        $form = new \Nette\Application\UI\Form();
        $form->getElementPrototype()->setAttribute('class','ajax');
        $form->addHidden('id');

        foreach ($inputs as $name => $option) {
            switch ($option['input']) {
                case 'password':
                    $form->addCheckbox('set_'.$name, 'Změnit heslo:')
                        ->addCondition(\Nette\Forms\Form::FILLED, true)
                        ->toggle('detail-'.$name);
                    $form->addPassword($name,$option['name'])->setAttribute('id','pass_'.$name);
                    break;
                case 'text':    $form->addText($name,$option['name']); break;
                case 'textarea':$form->addTextArea($name,$option['name']); break;
                case 'upload':  $form->addUpload($name,$option['name']); break;
                case 'integer': $form->addInteger($name,$option['name']); break;
                case 'select':  $form->addSelect($name, $option['name'], $option['select'])->setPrompt($name);
                    break;
                case 'checkbox':$form->addCheckbox($name,$option['name']); break;
                case 'date':    $form->addText($name,$option['name'])->setType('date');
                    break;
            }
        }

        $form->addSubmit('save','Uložit');
        $form->addSubmit('cancel')->getControlPrototype()->setName('button')->setHtml('<i class="fa fa-times" title="reset"></i>')->setValidationScope(FALSE);

        $form->onSuccess[] = function (\Nette\Application\UI\Form $form, $values) use ($model, $onSuccess, $onCancel, $onFailed) {
            if ($form['cancel']->isSubmittedBy())
                $onCancel($values, $form);
            else {
                try {
                    if (isset($values->password) && !$values->set_password)
                        unset($values->password);
                    elseif (isset($values->password))
                        $values->password = \Nette\Security\Passwords::hash($values->password);

                    if (isset($values->set_password))
                        unset($values->set_password);

                    $id = $model->save($values);

                } catch (\Exception $e) {
                    bdump($e);
                    $form->addError($e->getMessage());
                    return;
                }
                $onSuccess($id);
            }
        };

        return $form;

    }

}