<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Observer;

use Exception;
use FeWeDev\Base\Arrays;
use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Item;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class OrderItemOptionPrice implements ObserverInterface
{
    /** @var Arrays */
    protected $arrays;

    /** @var Data */
    protected $helper;

    /** @var Product */
    protected $productHelper;

    public function __construct(Arrays $arrays, Data $helper, Product $productHelper)
    {
        $this->arrays = $arrays;
        $this->helper = $helper;
        $this->productHelper = $productHelper;
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $transportObject */
        $transportObject = $observer->getData('data');

        $itemProductOptionData = $transportObject->getData('item_product_option_data');

        $optionType = $this->arrays->getValue(
            $itemProductOptionData,
            'option_type'
        );

        if ($optionType === 'product') {
            /** @var Item $item */
            $item = $transportObject->getData('item');

            $product = $item->getParentItem() ? $item->getParentItem()->getProduct() : $item->getProduct();

            $optionId = $this->arrays->getValue(
                $itemProductOptionData,
                'option_id'
            );

            /** @var Option $productOption */
            $productOption = $product->getOptionById($optionId);

            if ($productOption->getData('option_product_unattached')) {
                $price = $this->helper->getOptionPrice($productOption);

                $optionProduct = $this->helper->getOptionProduct($productOption);

                if ($optionProduct->getTypeId() === Configurable::TYPE_CODE) {
                    $optionValue = $this->arrays->getValue(
                        $itemProductOptionData,
                        'option_value'
                    );

                    foreach ($this->productHelper->getUsedProducts($optionProduct) as $usedProduct) {
                        if ($usedProduct->getId() == $optionValue) {
                            $price = $usedProduct->getFinalPrice();
                            break;
                        }
                    }
                }

                $transportObject->setData(
                    'price',
                    $price
                );
            }
        }
    }
}
