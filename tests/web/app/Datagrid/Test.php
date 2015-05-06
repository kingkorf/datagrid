<?php
namespace App\Datagrid;

use Abbert\Datagrid\Column\ActionColumn;
use Abbert\Datagrid\Column\ClosureColumn;
use Abbert\Datagrid\Datagrid;
use Abbert\Datagrid\Datasource\DoctrineSource;

class SecondTestColumn
{
    public function __invoke($row)
    {
        return $row->getAddress();
    }
}

class Test extends Datagrid
{
    public function init()
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Registry::get('em');

        $repo = $em->getRepository('App\Entity\Test');
        $source = new DoctrineSource($repo);

        $this->setDatasource($source);

        $this->addColumn('ID', new ClosureColumn(function ($row) {
            return $row->getId();
        }));

        $this->addColumn('Firstname', function ($row) {
            return $row->getFirstname();
        });

        $this->addColumn('Lastname', function ($row) {
            return $row->getLastname();
        });

        $this->addColumn('Address', new SecondTestColumn());

        $this->addColumn('Actions', new ActionColumn(function ($row) {
            return array(
                array(
                    'label' => 'dg-icon:fa fa-pencil',
                    'href' => '/people/update/id/' . $row->getId(),
                ),
                array(
                    'label' => 'dg-icon:fa fa-trash',
                    'href' => '/people/delete/id/' . $row->getId(),
                ),
            );
        }));

        // search
        $search = $this->getRequest()->get('search');

        if (!empty($search['value'])) {
            $source->getQueryBuilder()->andWhere(
                't.firstname LIKE :query OR t.lastname LIKE :query OR t.address LIKE :query'
            );
            $source->getQueryBuilder()->setParameter('query', '%' . $search['value'] . '%');
        }
    }
}
