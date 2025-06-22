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
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory;
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
class Tax extends CommonTaxCollector
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
    ): Tax {
        $items = $shippingAssignment->getItems();

        if (! $items) {
            return $this;
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

        $subtotal = $total->getTotalAmount('subtotal');
        $tax = $total->getTotalAmount('tax');
        $discountTaxCompensation = $total->getTotalAmount('discount_tax_compensation');
        $subtotalInclTax = $total->getData('subtotal_incl_tax');

        $baseSubtotal = $total->getBaseTotalAmount('subtotal');
        $baseTax = $total->getBaseTotalAmount('tax');
        $baseDiscountTaxCompensation = $total->getBaseTotalAmount('discount_tax_compensation');
        $baseSubtotalInclTax = $total->getData('base_subtotal_incl_tax');

        foreach ($taxDetails->getItems() as $itemTaxDetail) {
            $items = $shippingAssignment->getItems();

            /** @var Item $item */
            foreach ($items as $item) {
                $optionCalculationCodes = $item->getData('option_calculation_codes');

                if ($optionCalculationCodes === null) {
                    $optionCalculationCodes = [];
                }

                if (in_array(
                    $itemTaxDetail->getCode(),
                    $optionCalculationCodes
                )) {
                    $subtotal += $itemTaxDetail->getRowTotal();
                    $tax += $itemTaxDetail->getTaxAmount();
                    $discountTaxCompensation += $itemTaxDetail->getDiscountTaxCompensationAmount();
                    $subtotalInclTax += $itemTaxDetail->getRowTotalInclTax();

                    $item->setPrice($item->getPrice() + $itemTaxDetail->getRowTotal());
                    $item->setPriceInclTax($item->getPriceInclTax() + $itemTaxDetail->getRowTotalInclTax());
                    $item->setRowTotal($item->getRowTotal() + $itemTaxDetail->getRowTotal());
                    $item->setRowTotalInclTax($item->getRowTotalInclTax() + $itemTaxDetail->getRowTotalInclTax());
                    $item->setData(
                        'converted_price',
                        $this->priceCurrency->convert(
                            $item->getPrice(),
                            $store
                        )
                    );
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
                    $baseSubtotal += $itemBaseTaxDetail->getRowTotal();
                    $baseTax += $itemBaseTaxDetail->getTaxAmount();
                    $baseDiscountTaxCompensation += $itemBaseTaxDetail->getDiscountTaxCompensationAmount();
                    $baseSubtotalInclTax += $itemBaseTaxDetail->getRowTotalInclTax();

                    $item->setBasePrice($item->getBasePrice() + $itemBaseTaxDetail->getRowTotal());
                    $item->setBasePriceInclTax($item->getBasePriceInclTax() + $itemBaseTaxDetail->getRowTotalInclTax());
                    $item->setBaseRowTotal($item->getBaseRowTotal() + $itemBaseTaxDetail->getRowTotal());
                    $item->setBaseRowTotalInclTax(
                        $item->getBaseRowTotalInclTax() + $itemBaseTaxDetail->getRowTotalInclTax()
                    );
                }
            }
        }

        $total->setTotalAmount(
            'subtotal',
            $subtotal
        );
        $total->setBaseTotalAmount(
            'subtotal',
            $baseSubtotal
        );
        $total->setTotalAmount(
            'tax',
            $tax
        );
        $total->setBaseTotalAmount(
            'tax',
            $baseTax
        );
        $total->setTotalAmount(
            'discount_tax_compensation',
            $discountTaxCompensation
        );
        $total->setBaseTotalAmount(
            'discount_tax_compensation',
            $baseDiscountTaxCompensation
        );
        $total->setData(
            'subtotal_incl_tax',
            $subtotalInclTax
        );
        $total->setData(
            'base_subtotal_incl_tax',
            $baseSubtotalInclTax
        );
        $total->setData(
            'base_subtotal_total_incl_tax',
            $baseSubtotalInclTax
        );

        $address = $shippingAssignment->getShipping()->getAddress();

        $address->setSubtotal($subtotal);
        $address->setSubtotalInclTax($subtotalInclTax);
        $address->setBaseTaxAmount($baseTax);
        $address->setBaseSubtotal($baseSubtotal);
        $address->setBaseSubtotalTotalInclTax($baseSubtotalInclTax);

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

                    if ($option && $option->getType() === 'product' && $option->getData('option_product_unattached')) {
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
