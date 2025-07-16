<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Observer;

use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductOptionPriceConfigurationAfter implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    /** @var Registry */
    protected $registryHelper;

    public function __construct(Data $helper, Registry $registryHelper)
    {
        $this->helper = $helper;
        $this->registryHelper = $registryHelper;
    }

    public function execute(Observer $observer): void
    {
        /** @var DataObject $configObject */
        $configObject = $observer->getData('configObj');

        $config = $configObject->getData('config');

        $config = $this->helper->prepareProductOptionsConfig(
            $config,
            $this->getOptions()
        );

        $configObject->setData(
            'config',
            $config
        );
    }

    public function getOptions(): ?array
    {
        return $this->getProduct()->getOptions();
    }

    /**
     * @throws \LogicException
     */
    public function getProduct(): Product
    {
        if ($this->registryHelper->registry('current_product')) {
            return $this->registryHelper->registry('current_product');
        } else {
            throw new \LogicException('Product is not defined');
        }
    }
}
