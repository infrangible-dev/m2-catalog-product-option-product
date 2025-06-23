<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier;

use FeWeDev\Base\Arrays;
use Magento\Ui\Component\Form\Element\Checkbox;
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

    public const FIELD_OPTION_PRODUCT_CODE_ATTRIBUTE_NAME = 'option_product_code_attribute';

    public const FIELD_OPTION_UNATTACHED_NAME = 'option_product_unattached';

    public const FIELD_OPTION_ATTRIBUTE_OPTION_MAPPING_NAME = 'option_product_attribute_option_mapping';

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
                self::FIELD_OPTION_PRODUCT_CODE_ATTRIBUTE_NAME
            ],
            $this->getProductCodeAttributeFieldConfig(111)
        );

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
                static::FIELD_OPTION_UNATTACHED_NAME
            ],
            $this->getUnattachedFieldConfig(112)
        );

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
                static::FIELD_OPTION_ATTRIBUTE_OPTION_MAPPING_NAME
            ],
            $this->getAttributeOptionMappingFieldConfig(113)
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

    protected function getProductCodeAttributeFieldConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Product Code'),
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataScope'     => static::FIELD_OPTION_PRODUCT_CODE_ATTRIBUTE_NAME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder
                    ]
                ]
            ]
        ];
    }

    protected function getUnattachedFieldConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Unattached'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => static::FIELD_OPTION_UNATTACHED_NAME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder,
                        'value'         => '0',
                        'valueMap'      => [
                            'true'  => '1',
                            'false' => '0'
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getAttributeOptionMappingFieldConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Attribute Option Mapping'),
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataScope'     => static::FIELD_OPTION_ATTRIBUTE_OPTION_MAPPING_NAME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder
                    ]
                ]
            ]
        ];
    }
}
