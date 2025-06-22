<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Model\Sales\Total\Quote;

use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AssociatedTaxable extends CommonTaxCollector
{
    /** @var Data */
    protected $helper;

    /** @var Product */
    protected $productHelper;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var PriceCurrencyInterface */
    protected $priceCurrency;

    public function __construct(
        Config $taxConfig,
        TaxCalculationInterface $taxCalculationService,
        QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        AddressInterfaceFactory $customerAddressFactory,
        RegionInterfaceFactory $customerAddressRegionFactory,
        Data $helper,
        Product $productHelper,
        ManagerInterface $eventManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Helper\Data $taxHelper = null,
        QuoteDetailsItemExtensionInterfaceFactory $quoteDetailsItemExtensionInterfaceFactory = null,
        ?AccountManagementInterface $customerAccountManagement = null
    ) {
        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
            $taxHelper,
            $quoteDetailsItemExtensionInterfaceFactory,
            $customerAccountManagement
        );

        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->eventManager = $eventManager;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @throws \Exception
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): AssociatedTaxable {
        $items = $shippingAssignment->getItems();

        if (! $items) {
            return $this;
        }

        /** @var AbstractItem $item */
        foreach ($items as $item) {
            $itemAssociatedTaxables = $item->getData('associated_taxables');

            if (! $itemAssociatedTaxables) {
                $itemAssociatedTaxables = [];
            } else {
                // remove existing weee associated taxables
                foreach ($itemAssociatedTaxables as $iTaxable => $taxable) {
                    if ($taxable[ CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE ] ==
                        'catalog_product_option_product') {
                        unset($itemAssociatedTaxables[ $iTaxable ]);
                    }
                }
            }

            $item->setData(
                'associated_taxables',
                $itemAssociatedTaxables
            );
        }

        $store = $quote->getStore();

        $priceIncludesTax = $this->_config->priceIncludesTax($store);

        $itemDataObjects = $this->mapItems(
            $shippingAssignment,
            $priceIncludesTax,
            false
        );

        $quoteDetails = $this->prepareQuoteDetails(
            $shippingAssignment,
            $itemDataObjects
        );

        $taxDetails = $this->taxCalculationService->calculateTax(
            $quoteDetails,
            $store->getStoreId()
        );

        $baseItemDataObjects = $this->mapItems(
            $shippingAssignment,
            $priceIncludesTax,
            true
        );

        $baseQuoteDetails = $this->prepareQuoteDetails(
            $shippingAssignment,
            $baseItemDataObjects
        );

        $baseTaxDetails = $this->taxCalculationService->calculateTax(
            $baseQuoteDetails,
            $store->getStoreId()
        );

        $itemPriceList = [];

        foreach ($taxDetails->getItems() as $itemTaxDetail) {
            $items = $shippingAssignment->getItems();

            /** @var AbstractItem $item */
            foreach ($items as $item) {
                $optionCalculationCodes = $item->getData('option_calculation_codes');

                if ($optionCalculationCodes === null) {
                    $optionCalculationCodes = [];
                }

                if (in_array(
                    $itemTaxDetail->getCode(),
                    $optionCalculationCodes
                )) {
                    /** @var QuoteDetailsItemInterface $itemDataObject */
                    foreach ($itemDataObjects as $itemDataObject) {
                        if ($itemDataObject->getCode() == $itemTaxDetail->getCode()) {
                            $itemPriceList[ $item->getId() ][ $itemTaxDetail->getCode() ][ 'qty' ] =
                                $itemDataObject->getQuantity();
                        }
                    }

                    $itemPriceList[ $item->getId() ][ $itemTaxDetail->getCode() ][ 'price' ] =
                        $itemTaxDetail->getPriceInclTax();
                }
            }
        }

        foreach ($baseTaxDetails->getItems() as $itemBaseTaxDetail) {
            $items = $shippingAssignment->getItems();

            /** @var AbstractItem $item */
            foreach ($items as $item) {
                $optionCalculationCodes = $item->getData('option_calculation_codes');

                if ($optionCalculationCodes === null) {
                    $optionCalculationCodes = [];
                }

                if (in_array(
                    $itemBaseTaxDetail->getCode(),
                    $optionCalculationCodes
                )) {
                    $itemPriceList[ $item->getId() ][ $itemBaseTaxDetail->getCode() ][ 'base_price' ] =
                        $itemBaseTaxDetail->getPriceInclTax();
                }
            }
        }

        foreach ($itemPriceList as $itemId => $itemPrices) {
            /** @var AbstractItem $item */
            foreach ($items as $item) {
                if ($itemId == $item->getId()) {
                    foreach ($itemPrices as $itemPrice) {
                        $code = 'catalog_product_option_product_' . $this->getNextIncrement();

                        $taxClassId = $item->getProduct()->getData('tax_class_id');

                        $itemAssociatedTaxables = $item->getData('associated_taxables');

                        $associatedTaxable = [
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE                  => 'catalog_product_option_product',
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE                  => $code,
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE            => $itemPrice[ 'price' ],
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE       => $itemPrice[ 'base_price' ],
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY              => $itemPrice[ 'qty' ],
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID          => $taxClassId,
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX    => $priceIncludesTax,
                            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE => CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE
                        ];

                        $itemAssociatedTaxables[] = $associatedTaxable;

                        $item->setData(
                            'associated_taxables',
                            $itemAssociatedTaxables
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function mapItems(
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ): array {
        $items = $shippingAssignment->getItems();

        if (empty($items)) {
            return [];
        }

        $itemDataObjects = [];

        /** @var AbstractItem $item */
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $itemOptionIds = $item->getOptionByCode('option_ids');

            if ($itemOptionIds && $itemOptionIds->getValue()) {
                $optionIds = explode(
                    ',',
                    $itemOptionIds->getValue()
                );

                $optionCalculationCodes = [];

                foreach ($optionIds as $optionId) {
                    $option = $item->getProduct()->getOptionById($optionId);

                    if ($option && $option->getType() === 'product') {
                        $product = $this->helper->getOptionProduct($option);

                        $itemOption = $item->getOptionByCode('option_' . $option->getId());

                        $price = $this->helper->getOptionPrice($option);

                        if ($product->getTypeId() === Configurable::TYPE_CODE) {
                            foreach ($this->productHelper->getUsedProducts($product) as $usedProduct) {
                                if ($usedProduct->getId() == $itemOption->getValue()) {
                                    $product = $usedProduct;
                                    $price = $usedProduct->getFinalPrice();
                                    break;
                                }
                            }
                        }

                        if ($useBaseCurrency) {
                            $price = $this->priceCurrency->convert(
                                $price,
                                $product->getStore()
                            );
                        }

                        $innerTransportObject = new DataObject(
                            [
                                'product' => $item->getProduct(),
                                'option'  => $option,
                                'qty'     => 1
                            ]
                        );

                        $this->eventManager->dispatch(
                            'catalog_product_option_product_qty',
                            [
                                'data' => $innerTransportObject
                            ]
                        );

                        $code = sprintf(
                            'catalog_product_option_product-%d-%d',
                            $optionId,
                            $product->getId()
                        );
                        $qty = $innerTransportObject->getData('qty');

                        $taxClassKeyDataObject = $this->taxClassKeyDataObjectFactory->create();
                        $taxClassKeyDataObject->setType(TaxClassKeyInterface::TYPE_ID);
                        $taxClassKeyDataObject->setValue($product->getDataUsingMethod('tax_class_id'));

                        $itemDataObject = $this->quoteDetailsItemDataObjectFactory->create();

                        $itemDataObject->setCode($code);
                        $itemDataObject->setQuantity($qty);
                        $itemDataObject->setTaxClassKey($taxClassKeyDataObject);
                        $itemDataObject->setIsTaxIncluded($priceIncludesTax);
                        $itemDataObject->setType('catalog_product_option_product');
                        $itemDataObject->setUnitPrice($price);
                        $itemDataObject->setDiscountAmount(0);
                        $itemDataObject->setParentCode(null);

                        $itemDataObjects[] = [$itemDataObject];

                        $optionCalculationCodes[] = $code;
                    }
                }

                $item->setData(
                    'option_calculation_codes',
                    $optionCalculationCodes
                );
            }
        }

        return array_merge(
            [],
            ...
            $itemDataObjects
        );
    }
}
