<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Model\Product;

use Infrangible\CatalogProductOptionProduct\Helper\Data;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Option
{
    /** @var Data */
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @throws \Exception
     */
    public function aroundGetPrice(
        \Magento\Catalog\Model\Product\Option $subject,
        callable $proceed,
        $flag = false
    ) {
        if ($subject->getType() === 'product' && $subject->getProductId() && $subject->getOptionId()) {
            return $this->helper->getOptionPrice($subject);
        }

        return $proceed($flag);
    }

    /**
     * @throws \Exception
     */
    public function aroundGetRegularPrice(
        \Magento\Catalog\Model\Product\Option $subject,
        callable $proceed
    ) {
        if ($subject->getType() === 'product' && $subject->getProductId() && $subject->getOptionId()) {
            return $this->helper->getOptionPrice($subject);
        }

        return $proceed();
    }
}
