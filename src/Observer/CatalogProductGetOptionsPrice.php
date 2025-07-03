<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Observer;

use FeWeDev\Base\Variables;
use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductGetOptionsPrice implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    /** @var \Infrangible\Core\Helper\Product */
    protected $productHelper;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var \Magento\Tax\Helper\Data */
    protected $taxHelper;

    /** @var Variables */
    protected $variables;

    public function __construct(
        Data $helper,
        \Infrangible\Core\Helper\Product $productHelper,
        ManagerInterface $eventManager,
        \Magento\Tax\Helper\Data $taxHelper,
        Variables $variables
    ) {
        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->eventManager = $eventManager;
        $this->taxHelper = $taxHelper;
        $this->variables = $variables;
    }

    /**
     * @throws \Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var DataObject $transportObject */
        $transportObject = $observer->getData('data');

        /** @var Product $product */
        $product = $transportObject->getData('product');
        $optionsPriceUnattached = $transportObject->getData('options_price_unattached');

        $optionIds = $product->getCustomOption('option_ids');

        if ($optionIds && ! $this->variables->isEmpty($optionIds->getValue())) {
            foreach (explode(
                ',',
                $optionIds->getValue()
            ) as $optionId) {
                $option = $product->getOptionById($optionId);

                if ($option->getType() === 'product' && $option->getData('option_product_unattached')) {
                    $price = $this->helper->getOptionPrice(
                        $option,
                        $this->taxHelper->priceIncludesTax()
                    );

                    if ($product->getTypeId() === Configurable::TYPE_CODE) {
                        $customOption = $product->getCustomOption('option_' . $option->getId());

                        $usedProducts = $this->productHelper->getUsedProducts($product);

                        if ($usedProducts) {
                            foreach ($usedProducts as $usedProduct) {
                                if ($usedProduct->getId() == $customOption->getValue()) {
                                    $price =
                                        $usedProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(
                                            $this->taxHelper->priceIncludesTax() ? [] : ['tax', 'weee_tax']
                                        );
                                    break;
                                }
                            }
                        }
                    }

                    $innerTransportObject = new DataObject(
                        [
                            'product' => $product,
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

                    $optionsPriceUnattached += $price;
                }
            }
        }

        $transportObject->setData(
            'options_price_unattached',
            $optionsPriceUnattached
        );
    }
}
