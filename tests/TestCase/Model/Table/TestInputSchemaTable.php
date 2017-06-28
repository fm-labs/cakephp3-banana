<?php

namespace Banana\Test\TestCase\Model\Table;

use Banana\Model\TableInputSchema;
use Banana\Model\TableInputSchemaInterface;
use Banana\Model\TableInputSchemaTrait;
use Cake\ORM\Table;

/**
 * Class TestInputSchemaTable
 *
 * @package Banana\Test\TestCase\Model\Table
 */
class TestInputSchemaTable extends Table implements TableInputSchemaInterface
{
    use TableInputSchemaTrait;

    /**
     * @param array $config
     */
    public function initialize(array $config)
    {
        $this->table('posts');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

    /**
     * @param TableInputSchema $inputs
     * @return TableInputSchema
     */
    protected function _buildInputs(TableInputSchema $inputs)
    {
        $inputs
            ->addField('id', ['type' => 'hidden'])
            ->addField('title');

        return $inputs;
    }
}
