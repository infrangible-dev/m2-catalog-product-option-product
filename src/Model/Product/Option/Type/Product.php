<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Model\Product\Option\Type;

use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product extends DefaultType
{
    /** @var Data */
    protected $helper;

    public function __construct(
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $checkoutSession,
            $scopeConfig,
            $data
        );

        $this->helper = $helper;
    }

    /**
     * @throws LocalizedException
     */
    public function prepareForCart()
    {
        if ($this->getDataUsingMethod('is_valid')) {
            $userValue = $this->getDataUsingMethod('user_value');

            return $userValue != 0 ? $userValue : null;
        }

        throw new LocalizedException(
            __('We can\'t add the product to the cart because of an option validation issue.')
        );
    }

    /**
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getFormattedOptionValue($optionValue): string
    {
        $option = $this->getOption();

        $product = $this->helper->getOptionProduct($option);

        return $product ? $product->getName() : $option->getTitle();
    }

    /**
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getPrintableOptionValue($optionValue): string
    {
        $option = $this->getOption();

        $product = $this->helper->getOptionProduct($option);

        return $product ? $product->getName() : $option->getTitle();
    }
}
