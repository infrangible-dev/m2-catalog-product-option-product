<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        if (version_compare(
            $context->getVersion(),
            '1.2.0',
            '<'
        )) {
            $catalogProductOptionTableName = $setup->getTable('catalog_product_option');

            if (! $connection->tableColumnExists(
                $catalogProductOptionTableName,
                'option_product_code_attribute'
            )) {
                $connection->addColumn(
                    $catalogProductOptionTableName,
                    'option_product_code_attribute',
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Option Product Code Attribute'
                    ]
                );
            }
        }

        if (version_compare(
            $context->getVersion(),
            '1.5.0',
            '<'
        )) {
            $catalogProductOptionTableName = $setup->getTable('catalog_product_option');

            if (! $connection->tableColumnExists(
                $catalogProductOptionTableName,
                'option_product_unattached'
            )) {
                $connection->addColumn(
                    $catalogProductOptionTableName,
                    'option_product_unattached',
                    [
                        'type'     => Table::TYPE_SMALLINT,
                        'length'   => 5,
                        'nullable' => true,
                        'unsigned' => true,
                        'default'  => 0,
                        'comment'  => 'Unattached'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
