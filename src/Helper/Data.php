<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product\Option;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    /** @var Variables */
    protected $variables;

    /** @var Stores */
    protected $storeHelper;

    /** @var Product */
    protected $productHelper;

    /** @var \Magento\Catalog\Model\Product[] */
    private $products = [];

    public function __construct(Variables $variables, Stores $storeHelper, Product $productHelper)
    {
        $this->variables = $variables;
        $this->storeHelper = $storeHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * @throws \Exception
     */
    public function getOptionProduct(Option $option): ?\Magento\Catalog\Model\Product
    {
        $optionId = $option->getId();

        if (! array_key_exists(
            $optionId,
            $this->products
        )) {
            $sourceProductId = $this->variables->intValue($option->getData('option_product_id'));

            if ($sourceProductId) {
                $storeId = $this->storeHelper->getStoreId();

                $this->products[ $optionId ] = $this->productHelper->loadProduct(
                    $sourceProductId,
                    $storeId
                );
            }
        }

        return array_key_exists(
            $optionId,
            $this->products
        ) ? $this->products[ $optionId ] : null;
    }

    /**
     * @throws \Exception
     */
    public function getOptionPrice(Option $option, ?float $qty = null): float
    {
        if (! $option->getData(Option::KEY_PRICE)) {
            $optionPrice = 0;

            $product = $this->getOptionProduct($option);

            if ($product && $product->getId()) {
                $optionPrice = $product->getFinalPrice($qty);
            }

            $option->setPrice(floatval($optionPrice));
        }

        return $option->getData(Option::KEY_PRICE);
    }
}
