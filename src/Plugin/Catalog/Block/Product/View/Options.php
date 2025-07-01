<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Plugin\Catalog\Block\Product\View;

use FeWeDev\Base\Json;
use Infrangible\CatalogProductOptionProduct\Helper\Data;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Options
{
    /** @var Json */
    protected $json;

    /** @var Data */
    protected $helper;

    public function __construct(Json $json, Data $helper)
    {
        $this->json = $json;
        $this->helper = $helper;
    }

    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View\Options $subject, string $config): string
    {
        $config = $this->json->decode($config);

        $config = $this->helper->prepareProductOptionsConfig(
            $config,
            $subject->getOptions()
        );

        return $this->json->encode($config);
    }
}
