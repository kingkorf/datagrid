<?php
/*
 * This file is part of the abbert/datagrid package.
 *
 * (c) Albert Bakker <hello@abbert.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Abbert\Datagrid;

use Abbert\Datagrid\Column\AbstractColumn;
use Abbert\Datagrid\Column\ClosureColumn;
use Abbert\Datagrid\Datasource\AbstractSource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Datagrid
{
    /**
     * @var AbstractSource
     */
    protected $datasource;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $columns;

    /**
     * Rendered HTML will be stored here
     *
     * @var string
     */
    protected $html;

    /**
     * Datagrid's unique id
     * When not set, the class name will be used
     *
     * Important: a unique id is needed when multiple
     * instances of a datagrid are present, as this id
     * is used to determine for which datagrid a XMLHttpRequest
     * is intended.
     *
     * @var string
     */
    protected $id;

    public function __construct()
    {
        if (method_exists($this, 'init')) {
            call_user_func_array(array($this, 'init'), func_get_args());
        }
    }

    /**
     * @param AbstractSource $datasource
     * @return $this
     */
    public function setDatasource(AbstractSource $datasource)
    {
        $datasource->setDatagrid($this);
        $this->datasource = $datasource;

        return $this;
    }

    /**
     * @return AbstractSource
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Creates new Request when no request isset
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function render()
    {
        if (null === $this->datasource) {
            throw new \Exception('No datasource set');
        }

        // @todo get rid of symfony request dependency??

        if ($this->getRequest()->isXmlHttpRequest() && $this->isRequestForMe()) {
            $this->renderJson();
        } else {
            $this->renderHtml();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequestForMe()
    {
        return $this->getRequest()->get('datagrid') == $this->getId();
    }

    /**
     * Set Datagrid::html for later use in __toString
     *
     * @return $this
     */
    public function renderHtml()
    {
        // @todo datagrid view configurable path
        // @todo maybe custom renderers?

        ob_start();
        require __DIR__ . '/views/datagrid.phtml';
        $this->html = ob_get_clean();

        return $this;
    }

    /**
     * Send immediately
     */
    public function renderJson()
    {
        $totalCount = $this->getDatasource()->totalCount();
        $rows = $this->getDatasource()->listRows();

        $tmpData = [];
        foreach ($rows as $row) {
            $tmp = [];

            foreach ($this->getColumns() as $column) {
                $tmp[$column->getName()] = $column->format($row);
            }

            $tmpData[] = $tmp;
        }

        $data = [
            'draw' => $this->getRequest()->get('draw'), // @todo param names configurable
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $totalCount,
            'data' => $tmpData,
        ];

        $response = new JsonResponse();
        $response->setData($data);

        $response->send();
        exit;
    }

    public function addColumn()
    {
        $args = func_get_args();
        $args = array_pad($args, 3, null);

        $name = null;
        $options = [];

        if ($args[0] instanceof AbstractColumn) {
            $column = $args[0];

            if (is_array($args[1])) {
                $options = $args[1];
            }
        } else if (is_string($args[0])) {
            $name = $args[0];
            $column = $args[1];

            if (is_array($args[2])) {
                $options = $args[2];
            }
        } else {
            throw new \BadMethodCallException('Bad call, see documentation');
        }

        if ($column instanceof DatagridAwareInterface) {
            $column->setDatagrid($this);
        }

        if (!$column instanceof AbstractColumn && is_callable($column)) {
            $column = new ClosureColumn($column);
        }

        if (!$column instanceof AbstractColumn) {
            throw new \BadMethodCallException('Column should extend AbstractColumn');
        }

        if (is_string($name)) {
            $column->setName($name);
        }

        $column->setOptions($options);

        $this->columns[] = $column;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function __toString()
    {
        if (!is_string($this->html)) {
            return '';
        }

        return $this->html;
    }

    /**
     * @return string
     */
    public function getId()
    {
        if (null === $this->id) {
            $calledClass = explode('\\', get_called_class());
            $this->setId(end($calledClass));
        }

        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
