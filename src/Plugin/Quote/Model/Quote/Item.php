<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Quote\Model\Quote;

use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Item
{
    /** @var Data */
    protected $helper;

    /** @var Product */
    protected $productHelper;

    /** @var PriceCurrencyInterface */
    protected $priceCurrency;

    /** @var ManagerInterface */
    protected $eventManager;

    public function __construct(
        Data $helper,
        Product $productHelper,
        PriceCurrencyInterface $priceCurrency,
        ManagerInterface $eventManager
    ) {
        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->priceCurrency = $priceCurrency;
        $this->eventManager = $eventManager;
    }

    /**
     * @throws \Exception
     */
    public function afterCalcRowTotal(
        \Magento\Quote\Model\Quote\Item $subject,
        \Magento\Quote\Model\Quote\Item $result
    ): \Magento\Quote\Model\Quote\Item {
        $itemOptionIds = $subject->getOptionByCode('option_ids');

        if ($itemOptionIds && $itemOptionIds->getValue()) {
            $optionIds = explode(
                ',',
                $itemOptionIds->getValue()
            );

            $optionPrices = 0;

            foreach ($optionIds as $optionId) {
                $option = $subject->getProduct()->getOptionById($optionId);

                if ($option && $option->getType() === 'product') {
                    $product = $this->helper->getOptionProduct($option);

                    $itemOption = $subject->getOptionByCode('option_' . $option->getId());

                    $price = $this->helper->getOptionPrice($option);

                    if ($product->getTypeId() === Configurable::TYPE_CODE) {
                        foreach ($this->productHelper->getUsedProducts($product) as $usedProduct) {
                            if ($usedProduct->getId() == $itemOption->getValue()) {
                                $price = $usedProduct->getFinalPrice();
                                break;
                            }
                        }
                    }

                    $innerTransportObject = new DataObject(
                        [
                            'product' => $subject->getProduct(),
                            'option'  => $option,
                            'price'   => $price
                        ]
                    );

                    $this->eventManager->dispatch(
                        'catalog_product_option_product_price',
                        [
                            'data' => $innerTransportObject
                        ]
                    );

                    $price = $innerTransportObject->getData('price');

                    $optionPrices += $price;
                }
            }

            if ($optionPrices > 0) {
                #$result->setPrice($result->getPrice() + $optionPrices);
                #$result->setBasePrice($result->getBasePrice() + $optionPrices);
                #$result->setPriceInclTax($result->getPriceInclTax() + $optionPrices);
                #$result->setBasePriceInclTax($result->getBasePriceInclTax() + $optionPrices);
                #$result->setRowTotal($this->priceCurrency->roundPrice($result->getRowTotal() + $optionPrices));
                #$result->setBaseRowTotal($this->priceCurrency->roundPrice($result->getBaseRowTotal() + $optionPrices));
                #$result->setRowTotalInclTax($result->getRowTotalInclTax() + $optionPrices);
                #$result->setBaseRowTotalInclTax($result->getBaseRowTotalInclTax() + $optionPrices);
                $result->setData(
                    'calculation_price',
                    $result->getCalculationPrice() + $optionPrices
                );
                $result->setData(
                    'converted_price',
                    $result->getConvertedPrice() + $optionPrices
                );
            }
        }

        return $result;
    }
}
