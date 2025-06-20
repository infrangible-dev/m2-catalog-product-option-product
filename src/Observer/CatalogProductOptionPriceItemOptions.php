<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Observer;

use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductOptionPriceItemOptions implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    /** @var Product */
    protected $productHelper;

    /** @var ManagerInterface */
    protected $eventManager;

    public function __construct(Data $helper, Product $productHelper, ManagerInterface $eventManager)
    {
        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * @throws \Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var DataObject $transportObject */
        $transportObject = $observer->getData('data');

        /** @var AbstractItem $item */
        $item = $transportObject->getData('item');
        $optionId = $transportObject->getData('option_id');

        $option = $item->getProduct()->getOptionById($optionId);

        $type = $option->getType();

        if ($type === 'product') {
            $product = $this->helper->getOptionProduct($option);

            $price = $this->helper->getOptionPrice($option);

            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                $itemOption = $item->getOptionByCode('option_' . $option->getId());

                foreach ($this->productHelper->getUsedProducts($product) as $usedProduct) {
                    if ($usedProduct->getId() == $itemOption->getValue()) {
                        $price = $usedProduct->getFinalPrice();
                        break;
                    }
                }
            }

            $innerTransportObject = new DataObject(
                [
                    'product' => $item->getProduct(),
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

            $transportObject->setData(
                'price',
                $price
            );
            $transportObject->setData(
                'display',
                true
            );
        }
    }
}
