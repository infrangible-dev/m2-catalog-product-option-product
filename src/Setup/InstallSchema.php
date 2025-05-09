<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @throws \Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $catalogProductOptionTableName = $setup->getTable('catalog_product_option');

        if (! $connection->tableColumnExists(
            $catalogProductOptionTableName,
            'option_product_id'
        )) {
            $connection->addColumn(
                $catalogProductOptionTableName,
                'option_product_id',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'length'   => 10,
                    'nullable' => true,
                    'unsigned' => true,
                    'comment'  => 'Option Product ID'
                ]
            );
        }

        $setup->endSetup();
    }
}
