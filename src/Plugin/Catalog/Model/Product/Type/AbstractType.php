<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Model\Product\Type;

use FeWeDev\Base\Variables;
use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Attribute;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AbstractType
{
    /** @var Data */
    protected $helper;

    /** @var \Infrangible\Core\Helper\Product */
    protected $productHelper;

    /** @var Attribute */
    protected $attributeHelper;

    /** @var Variables */
    protected $variables;

    public function __construct(
        Data $helper,
        \Infrangible\Core\Helper\Product $productHelper,
        Attribute $attributeHelper,
        Variables $variables
    ) {
        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->attributeHelper = $attributeHelper;
        $this->variables = $variables;
    }

    /**
     * @throws \Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetOrderOptions(
        Product\Type\AbstractType $subject,
        array $result,
        Product $product
    ): array {
        $optionIds = $product->getCustomOption('option_ids');

        if ($optionIds) {
            foreach (explode(
                ',',
                $optionIds->getValue() ?? ''
            ) as $optionId) {
                $option = $product->getOptionById($optionId);

                if ($option && $option->getType() === 'product') {
                    $valueCodeAttributeCode = $option->getData('option_product_code_attribute');

                    if ($valueCodeAttributeCode) {
                        $confItemOption =
                            $product->getCustomOption(Product\Type\AbstractType::OPTION_PREFIX . $option->getId());

                        $optionProduct = $this->helper->getOptionProduct($option);

                        $optionValueCode = null;

                        if ($optionProduct->getTypeId() === Configurable::TYPE_CODE) {
                            $valueId = $confItemOption->getValue();

                            foreach ($this->productHelper->getUsedProducts($optionProduct) as $usedProduct) {
                                if ($usedProduct->getId() == $valueId) {
                                    $optionValueCode = $this->attributeHelper->getProductAttributeValue(
                                        $this->variables->intValue($usedProduct->getId()),
                                        $valueCodeAttributeCode,
                                        $this->variables->intValue($usedProduct->getStoreId())
                                    );

                                    break;
                                }
                            }
                        } else {
                            $optionValueCode = $this->attributeHelper->getProductAttributeValue(
                                $this->variables->intValue($optionProduct->getId()),
                                $valueCodeAttributeCode,
                                $this->variables->intValue($optionProduct->getStoreId())
                            );
                        }

                        if ($optionValueCode) {
                            foreach ($result[ 'options' ] as $optionKey => $optionData) {
                                if ($optionData[ 'option_id' ] === $optionId) {
                                    $result[ 'options' ][ $optionKey ][ 'option_value_code' ] = $optionValueCode;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
