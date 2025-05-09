<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Block\Product\View;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Magento\Catalog\Model\Product\Option;

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

    public function __construct(
        Arrays $arrays,
        Json $json
    ) {
        $this->arrays = $arrays;
        $this->json = $json;
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

                unset($config[ $optionId ]);

                $config[ $optionId ][ $option->getData('option_product_id') ] = $priceConfiguration;
            }
        }

        return $this->json->encode($config);
    }
}
