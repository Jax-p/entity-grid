<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Jax_p\EntityGrid;

/**
 * Class EntityGrid
 * @package Jax_p\EntityGrid
 */
class EntityGrid extends \Nette\Application\UI\Control
{
    /** @var SessionSection */
    private $session;

    /** @var Nette\Application\UI\Control */
    private $factory;

    /** @var array */
    private $options;

    /** @var PHP class */
    private $model;

    /** @var boolean */
    public $create = true;

    /** @var boolean */
    public $form_renderer = true;


    /**
     * EntityGrid constructor.
     * @param $model
     * @param $options
     * @param $session
     * @param null $form_renderer
     */
    public function __construct($model, $options, $session, $form_renderer = null) {
        parent::__construct();
        $this->model = $model;
        $this->session = $session;
        $this->options = $options;
        $this->form_renderer = $form_renderer;
    }


    /**
     * Default render method, renders grid
     * @param int $page
     */
    public function render($page = 1)
    {
        $this->setSessionFiltres();
        $this->getComponent('searchForm')->setDefaults($this->session->search);
        $this->renderItemsList($this->model, $page);
        $this->template->setFile(dirname(__FILE__) . '/templates/grid.latte');
        $this->template->link = $this->parent->getName();

        $items = $this->options['items'];
        foreach ($items as $key => $item) {
            if (isset($item['hidden'])) unset($items[$key]);
        }

        $this->template->_items = $items;
        $this->template->options= $this->options;
        $this->template->_key   = $this->options['key'];
        $this->template->_name  = $this->options['name'];

        $this->template->parent_name = $this->parent->getName();
        $this->template->render();
    }


    /**
     * Renders detail of entity with form
     * @param ArrayHash $item
     */
    public function renderDetail($item = null)
    {
        if ($item) $this->getComponent('detailForm')->setDefaults($item);
        $this->template->setFile(dirname(__FILE__) . '/templates/detail.latte');
        $this->template->render();
    }


    /**
     * Renders grid items
     * @param $model
     * @param $page
     */
    protected function renderItemsList($model, $page) {
        $items = $model->getAll();
        $this->setSessionFiltres();
        $this->filterResults($items);

        $pg = new \Nette\Utils\Paginator();
        $pg->setItemsPerPage($this->session->pp);
        $pg->setPage($page);
        $pg->setItemCount($items->count());
        $items->limit($pg->getItemsPerPage(), $pg->getOffset());

        $this->template->paginator = $pg;
        $this->template->items = $items;
    }


    /**
     * Deletes item based on ID and current $model
     * @param $item_id
     */
    public function handleDeleteItem($item_id) {
        $this->model->get($item_id)->delete();
        $this->redrawControl();
    }


    /**
     * Handles per page value and redraws snippet
     * @param $pp
     */
    public function handlePerPage($pp) {
        $this->template->pp = $this->session->pp = $pp;
        $this->redrawControl();
    }


    /**
     * Handles order value and redraws snippet
     * @param $order
     */
    public function handleSetOrder($order) {
        $this->template->order = $this->session->order = $order;
        $this->redrawControl();
    }


    /**
     * Hides column and redraws control
     * @param type $col
     */
    public function handleHideCol($col) {
        $this->session->hide[$col] = $col;
        $this->redrawControl();
    }


    /**
     * Shows column and redraws control
     * @param type $col
     */
    public function handleShowCol($col) {
        unset($this->session->hide[$col]);
        $this->redrawControl();
    }


    /**
     * Search and order by sessions parameters
     * @param &$items
     */
    protected function filterResults(&$items) {

        $values = $this->session->search;

        foreach ($values as $key => $val) {

            if (!$val) continue;
            if (!empty($this->options['items'][$key]['related'])) continue;
            if (strpos($key,'_to') && !empty($this->options['items'][substr($key,0,-3)])) continue;

            switch ($this->options['items'][$key]['input']) {
                case 'date':
                    $items->where("created >= ?", $val);
                    if ($values[$key.'_to'])
                        $items->where("created <= ?", $values[$key.'_to']);
                    unset($values[$key.'_to']);
                    break;
                case 'integer':
                    $items->where($key, $val);
                    break;
                default:
                    $items->where($key . ' LIKE ?', '%' . $val . '%');
                    break;
            }
        }

        $order = $this->session->order;
        // if order is based on JOINed table
        if ($order && substr($order, 0, 1) == ':') {
            $desc_pos = strpos($order, 'DESC');
            if ($desc_pos !== false) {
                $order = substr($order, 0, $desc_pos);
                $items->group($order)->order('COUNT(' . $order . ') DESC');
            } else {
                $asc_pos = strpos($order, 'ASC');
                $order = substr_replace($order, '', $asc_pos);
                $items->group($order)->order('COUNT(' . $order . ')');
            }
        } else if ($order) {
            $items->order($order);
        }
    }


    /**
     * Setups default session value and sets "perpage" and "order" to template
     */
    protected function setSessionFiltres() {
        if (!$this->session->pp) $this->session->pp = 10;
        if (!$this->session->order) $this->session->order = 'created DESC';
        if (!$this->session->search) $this->session->search = array();
        if (!$this->session->hide) $this->session->hide = array();
        $this->template->pp = $this->session->pp ? $this->session->pp : 10;
        $this->template->order = $this->session->order;
        $this->template->hide = $this->session->hide;
    }


    /**
     * Form component
     * @return Form
     */
    protected function createComponentSearchForm() {
        $inputs = [];
        $this->factory = new SearchFormFactory();

        foreach($this->options['items'] as $key => $option) {
            if (isset($option['input'])) {
                $inputs[$key] = $option;
            }
        }

        $form = $this->factory->create(
            $inputs, function ($values) {
            foreach ($values as $key => $v)
                $this->session->search[$key] = $v ? $v : null;
            $this->redrawControl();
        }, function ($values) {
            foreach ($values as $key => $v)
                $this->session->search[$key] = null;
            $this->parent->payload->clear_form = 'frm-entityGrid-searchForm';
            $this->redrawControl();
        }
        );

        return $this->form_renderer ? $this->form_renderer->render($form) : $form;
    }


    /**
     * Form component
     * @return Form
     */
    protected function createComponentDetailForm() {
        $inputs = [];
        $this->factory = new DetailFormFactory();

        /** fill form's input array */
        foreach($this->options['items'] as $key => $option) {
            if (isset($option['editable'])) {
                $inputs[$key] = $option;
            }
        }

        /** @param ArrayHash, Model, OnSuccess, OnCancel, OnFailed */
        $form = $this->factory->create(
            $inputs, $this->model,
            function ($values) {
                $this->getPresenter()->flashMessage('Změny uloženy');
                $this->getPresenter()->redirect(':default');
            },
            function ($values) {
                $this->getPresenter()->redirect(':default');
            },
            function ($values) {
                $this->getPresenter()->flashMessage('Nastala chyba, opakujte později');
                $this->getPresenter()->redirect('this');
            }
        );

        return $this->form_renderer ? $this->form_renderer->render($form) : $form;
    }
    
}