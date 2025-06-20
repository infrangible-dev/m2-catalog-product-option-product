<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductOptionPriceItemRenderer implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var DataObject $transportObject */
        $transportObject = $observer->getData('data');

        /** @var AbstractItem $item */
        $item = $transportObject->getData('item');
        $optionId = $transportObject->getData('option_id');

        $productOption = $item->getProduct()->getOptionById($optionId);

        $type = $productOption->getType();

        if ($type === 'product') {
            $transportObject->setData(
                'display',
                false
            );
        }
    }
}
