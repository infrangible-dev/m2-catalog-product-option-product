<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Block\Product\View;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\CatalogProductOptionProduct\Helper\Data;
use Infrangible\Core\Helper\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Options
{
    /** @var Json */
    protected $json;

    /** @var Arrays */
    protected $arrays;

    /** @var Data */
    protected $helper;

    /** @var Product */
    protected $productHelper;

    public function __construct(Arrays $arrays, Json $json, Data $helper, Product $productHelper)
    {
        $this->arrays = $arrays;
        $this->json = $json;
        $this->helper = $helper;
        $this->productHelper = $productHelper;
    }

    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View\Options $subject, string $config): string
    {
        $config = $this->json->decode($config);

        /** @var Option $option */
        foreach ($subject->getOptions() as $option) {
            if ($option->getType() === 'product') {
                $optionId = $option->getId();

                $priceConfiguration = $this->arrays->getValue(
                    $config,
                    $optionId,
                    []
                );

                try {
                    $product = $this->helper->getOptionProduct($option);

                    unset($config[ $optionId ]);

                    $config[ $optionId ][ $product->getId() ] = $priceConfiguration;

                    if ($product->getTypeId() === Configurable::TYPE_CODE) {
                        foreach ($this->productHelper->getUsedProductsPrices($product) as $usedProductId =>
                            $usedProductsPrices) {

                            $config[ $optionId ][ $usedProductId ][ 'prices' ] = $usedProductsPrices;
                        }
                    }
                } catch (\Exception $exception) {
                }
            }
        }

        return $this->json->encode($config);
    }
}
