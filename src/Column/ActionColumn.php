<?php
/*
 * This file is part of the abbert/datagrid package.
 *
 * (c) Albert Bakker <hello@abbert.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Abbert\Datagrid\Column;

/**
 * Class ActionColumn
 * @package Abbert\Datagrid\Column
 *
 * @todo improve this
 */
class ActionColumn extends AbstractColumn
{
    /**
     * @var array
     */
    protected $closure;

    public function __construct($closure)
    {
        if (!is_callable($closure)) {
            throw new \InvalidArgumentException('Parameter must be callable');
        }

        $this->closure = $closure;

        // default options
        $this->setOption('hideName', true);
    }

    /**
     * @param $row
     * @return mixed
     */
    public function format($row)
    {
        $actions = $this->closure->__invoke($row);

        $html = '<div class="btn-group">';

        foreach ($actions as $action) {

            $href = !empty($action['href']) ? $action['href'] : '#';
            $label = !empty($action['label']) ? $action['label'] : 'label missing';

            if (strstr($label, 'dg-icon')) {
                $label = '<span class="' . str_replace('dg-icon:', '', $label) . '"></span>';
            }

            $link = '<a href="' . $href . '" class="btn btn-default">' . $label . '</a>';


            $html .= $link;
        }

        return $html;
    }
}
