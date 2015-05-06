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
use Abbert\Datagrid\DatagridAwareInterface;
use Abbert\Datagrid\DatagridAwareTrait;

/**
 * Class ClosureColumn
 * @package Abbert\Datagrid\Column
 */
class ClosureColumn extends AbstractColumn implements DatagridAwareInterface
{
    use DatagridAwareTrait;

    protected $callable;

    /**
     * @param $callable
     */
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('Parameter must be callable');
        }

        $this->callable = $callable;
    }

    /**
     * @param $row
     * @return string
     * @throws \Exception
     */
    public function format($row)
    {
        return (string) $this->callable->__invoke($row);
    }
}
