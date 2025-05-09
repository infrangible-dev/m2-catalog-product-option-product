<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier;

use FeWeDev\Base\Arrays;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CustomOptions
{
    public const FIELD_OPTION_PRODUCT_ID_NAME = 'option_product_id';

    /** @var Arrays */
    protected $arrays;

    public function __construct(Arrays $arrays)
    {
        $this->arrays = $arrays;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterModifyMeta(
        \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions $subject,
        array $meta
    ): array {
        $meta = $this->arrays->addDeepValue(
            $meta,
            [
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::GROUP_CUSTOM_OPTIONS_NAME,
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::GRID_OPTIONS_NAME,
                'children',
                'record',
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::CONTAINER_OPTION,
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::CONTAINER_TYPE_STATIC_NAME,
                'children',
                self::FIELD_OPTION_PRODUCT_ID_NAME
            ],
            $this->getProductIdFieldConfig(110)
        );

        return $this->arrays->addDeepValue(
            $meta,
            [
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::GROUP_CUSTOM_OPTIONS_NAME,
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::GRID_OPTIONS_NAME,
                'children',
                'record',
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::CONTAINER_OPTION,
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::CONTAINER_COMMON_NAME,
                'children',
                \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::FIELD_TYPE_NAME,
                'arguments',
                'data',
                'config',
                'groupsConfig',
                'product'
            ],
            [
                'values'  => ['product'],
                'indexes' => [
                    \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::CONTAINER_TYPE_STATIC_NAME,
                    \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::FIELD_SKU_NAME,
                    CustomOptions::FIELD_OPTION_PRODUCT_ID_NAME
                ]
            ]
        );
    }

    protected function getProductIdFieldConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Product'),
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataScope'     => static::FIELD_OPTION_PRODUCT_ID_NAME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder
                    ],
                ],
            ],
        ];
    }
}
