<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\CatalogProductOptionProduct\Block\Product\View\Options\Type;

use FeWeDev\Base\Json;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Block\Product\View\Options\AbstractOptions;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Pricing\Price\CalculateCustomOptionCatalogRule;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\Swatch;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Product extends AbstractOptions
{
    /** @var \Infrangible\CatalogProductOptionProduct\Helper\Data */
    protected $helper;

    /** @var Stores */
    protected $storeHelper;

    /** @var Media */
    protected $swatchMediaHelper;

    /** @var Json */
    protected $json;

    /** @var \Magento\Swatches\Helper\Data */
    protected $swatchHelper;

    /** @var \Magento\ConfigurableProduct\Helper\Data */
    protected $configurableHelper;

    /** @var ConfigurableAttributeData */
    protected $configurableAttributeData;

    /** @var Format */
    protected $localeFormat;

    /** @var Prices */
    protected $variationPrices;

    /** @var UrlBuilder */
    protected $imageUrlBuilder;

    /** @var \Infrangible\Core\Helper\Product */
    protected $productHelper;

    public function __construct(
        Context $context,
        Data $pricingHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Infrangible\CatalogProductOptionProduct\Helper\Data $helper,
        Stores $storeHelper,
        Media $swatchMediaHelper,
        Json $json,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\ConfigurableProduct\Helper\Data $configurableHelper,
        ConfigurableAttributeData $configurableAttributeData,
        Format $localeFormat,
        Prices $variationPrices,
        UrlBuilder $urlBuilder,
        \Infrangible\Core\Helper\Product $productHelper,
        array $data = [],
        CalculateCustomOptionCatalogRule $calculateCustomOptionCatalogRule = null,
        CalculatorInterface $calculator = null,
        PriceCurrencyInterface $priceCurrency = null
    ) {
        parent::__construct(
            $context,
            $pricingHelper,
            $catalogData,
            $data,
            $calculateCustomOptionCatalogRule,
            $calculator,
            $priceCurrency
        );

        $this->helper = $helper;
        $this->storeHelper = $storeHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->json = $json;
        $this->swatchHelper = $swatchHelper;
        $this->configurableHelper = $configurableHelper;
        $this->configurableAttributeData = $configurableAttributeData;
        $this->localeFormat = $localeFormat;
        $this->variationPrices = $variationPrices;
        $this->imageUrlBuilder = $urlBuilder;
        $this->productHelper = $productHelper;
    }

    public function getPreconfiguredValue(Option $option)
    {
        return $this->getProduct()->getPreconfiguredValues()->getData('options/' . $option->getId());
    }

    public function getCurrencyByStore(float $price)
    {
        return $this->pricingHelper->currencyByStore(
            $price,
            $this->getProduct()->getStore(),
            false
        );
    }

    public function formatPrice(float $price): string
    {
        return parent::_formatPrice(
            [
                'is_percent'    => false,
                'pricing_value' => $price
            ]
        );
    }

    public function getProduct(): ?\Magento\Catalog\Model\Product
    {
        try {
            return $this->helper->getOptionProduct($this->getOption());
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getNumberSwatchesPerProduct(): string
    {
        return $this->storeHelper->getStoreConfig('catalog/frontend/swatches_per_product');
    }

    public function getJsonConfig(): string
    {
        try {
            $store = $this->storeHelper->getStore();
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception);

            return '{}';
        }

        $currentProduct = $this->getProduct();

        $options = $this->configurableHelper->getOptions(
            $currentProduct,
            $this->productHelper->getUsedProducts($currentProduct)
        );

        $attributesData = $this->configurableAttributeData->getAttributesData(
            $currentProduct,
            $options
        );

        try {
            $config = [
                'attributes'                     => $attributesData[ 'attributes' ],
                'template'                       => str_replace(
                    '%s',
                    '<%- data.price %>',
                    $store->getCurrentCurrency()->getOutputFormat()
                ),
                'currencyFormat'                 => $store->getCurrentCurrency()->getOutputFormat(),
                'optionPrices'                   => $this->productHelper->getUsedProductsPrices($currentProduct),
                'priceFormat'                    => $this->localeFormat->getPriceFormat(),
                'prices'                         => $this->variationPrices->getFormattedPrices(
                    $this->getProduct()->getPriceInfo()
                ),
                'productId'                      => $currentProduct->getId(),
                'chooseText'                     => __('Choose an Option...'),
                'images'                         => $this->getOptionImages(),
                'index'                          => $options[ 'index' ] ?? [],
                'salable'                        => $options[ 'salable' ] ?? [],
                'canDisplayShowOutOfStockStatus' => $options[ 'canDisplayShowOutOfStockStatus' ] ?? false,
                'channel'                        => SalesChannelInterface::TYPE_WEBSITE,
                'salesChannelCode'               => $this->storeHelper->getWebsite()->getCode(),
                'sku'                            => $this->productHelper->getUsedProductsSkus($currentProduct)
            ];
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception);

            return '{}';
        }

        /** @noinspection PhpUndefinedMethodInspection */
        if ($currentProduct->hasPreconfiguredValues() && ! empty($attributesData[ 'defaultValues' ])) {
            $config[ 'defaultValues' ] = $attributesData[ 'defaultValues' ];
        }

        return $this->json->encode($config);
    }

    private function getSwatchAttributesData(): array
    {
        return $this->swatchHelper->getSwatchAttributesAsArray($this->getProduct());
    }

    private function getConfigurableOptionsIds(array $attributeData): array
    {
        $ids = [];

        foreach ($this->productHelper->getUsedProducts($this->getProduct()) as $product) {
            /** @var Attribute $attribute */
            foreach ($this->configurableHelper->getAllowAttributes($this->getProduct()) as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();

                if (isset($attributeData[ $productAttributeId ])) {
                    $ids[ $product->getData($productAttribute->getAttributeCode()) ] = 1;
                }
            }
        }

        return array_keys($ids);
    }

    private function getOptionImages(): array
    {
        $images = [];

        foreach ($this->productHelper->getUsedProducts($this->getProduct()) as $product) {
            $productImages = $this->configurableHelper->getGalleryImages($product) ? : [];

            foreach ($productImages as $image) {
                $images[ $product->getId() ][] = [
                    'thumb'    => $image->getData('small_image_url'),
                    'img'      => $image->getData('medium_image_url'),
                    'full'     => $image->getData('large_image_url'),
                    'caption'  => $image->getLabel(),
                    'position' => $image->getPosition(),
                    'isMain'   => $image->getFile() == $product->getImage(),
                    'type'     => $image->getMediaType() ? str_replace(
                        'external-',
                        '',
                        $image->getMediaType()
                    ) : '',
                    'videoUrl' => $image->getVideoUrl(),
                ];
            }
        }

        return $images;
    }

    public function getJsonSwatchConfig(): string
    {
        $attributesData = $this->getSwatchAttributesData();

        $allOptionIds = $this->getConfigurableOptionsIds($attributesData);

        $swatchesData = $this->swatchHelper->getSwatchesByOptionsId($allOptionIds);

        $config = [];

        foreach ($attributesData as $attributeId => $attributeDataArray) {
            if (isset($attributeDataArray[ 'options' ])) {
                $config[ $attributeId ] = $this->addSwatchDataForAttribute(
                    $attributeDataArray[ 'options' ],
                    $swatchesData,
                    $attributeDataArray
                );
            }

            if (isset($attributeDataArray[ 'additional_data' ])) {
                $config[ $attributeId ][ 'additional_data' ] = $attributeDataArray[ 'additional_data' ];
            }
        }

        return $this->json->encode($config);
    }

    private function addSwatchDataForAttribute(
        array $options,
        array $swatchesCollectionArray,
        array $attributeDataArray
    ): array {
        $result = [];

        foreach ($options as $optionId => $label) {
            if (isset($swatchesCollectionArray[ $optionId ])) {
                $result[ $optionId ] = $this->extractNecessarySwatchData($swatchesCollectionArray[ $optionId ]);
                $result[ $optionId ] = $this->addAdditionalMediaData(
                    $result[ $optionId ],
                    $optionId,
                    $attributeDataArray
                );
                $result[ $optionId ][ 'label' ] = $label;
            }
        }

        return $result;
    }

    private function extractNecessarySwatchData(array $swatchDataArray): array
    {
        $result[ 'type' ] = $swatchDataArray[ 'type' ];

        if ($result[ 'type' ] == Swatch::SWATCH_TYPE_VISUAL_IMAGE && ! empty($swatchDataArray[ 'value' ])) {
            $result[ 'value' ] = $this->swatchMediaHelper->getSwatchAttributeImage(
                Swatch::SWATCH_IMAGE_NAME,
                $swatchDataArray[ 'value' ]
            );
            $result[ 'thumb' ] = $this->swatchMediaHelper->getSwatchAttributeImage(
                Swatch::SWATCH_THUMBNAIL_NAME,
                $swatchDataArray[ 'value' ]
            );
        } else {
            $result[ 'value' ] = $swatchDataArray[ 'value' ];
        }

        return $result;
    }

    private function addAdditionalMediaData(array $swatch, int $optionId, array $attributeDataArray): array
    {
        if (isset($attributeDataArray[ 'use_product_image_for_swatch' ]) &&
            $attributeDataArray[ 'use_product_image_for_swatch' ]) {

            $variationMedia = $this->getVariationMedia(
                $attributeDataArray[ 'attribute_code' ],
                $optionId
            );

            if (! empty($variationMedia)) {
                $swatch[ 'type' ] = Swatch::SWATCH_TYPE_VISUAL_IMAGE;
                $swatch = array_merge(
                    $swatch,
                    $variationMedia
                );
            }
        }
        return $swatch;
    }

    private function getVariationMedia(string $attributeCode, int $optionId): array
    {
        /** @var \Magento\Catalog\Model\Product $variationProduct */
        $variationProduct = $this->swatchHelper->loadFirstVariationWithSwatchImage(
            $this->getProduct(),
            [$attributeCode => $optionId]
        );

        if (! $variationProduct) {
            $variationProduct = $this->swatchHelper->loadFirstVariationWithImage(
                $this->getProduct(),
                [$attributeCode => $optionId]
            );
        }

        $variationMediaArray = [];

        if ($variationProduct) {
            $variationMediaArray = [
                'value' => $this->getSwatchProductImage(
                    $variationProduct,
                    Swatch::SWATCH_IMAGE_NAME
                ),
                'thumb' => $this->getSwatchProductImage(
                    $variationProduct,
                    Swatch::SWATCH_THUMBNAIL_NAME
                ),
            ];
        }

        return $variationMediaArray;
    }

    private function getSwatchProductImage(\Magento\Catalog\Model\Product $childProduct, string $imageType): string
    {
        if ($this->isProductHasImage(
            $childProduct,
            Swatch::SWATCH_IMAGE_NAME
        )) {
            $swatchImageId = $imageType;
            $imageAttributes = ['type' => Swatch::SWATCH_IMAGE_NAME];
        } elseif ($this->isProductHasImage(
            $childProduct,
            'image'
        )) {
            $swatchImageId = $imageType == Swatch::SWATCH_IMAGE_NAME ? 'swatch_image_base' : 'swatch_thumb_base';
            $imageAttributes = ['type' => 'image'];
        }

        if (! empty($swatchImageId) && ! empty($imageAttributes[ 'type' ])) {
            return $this->imageUrlBuilder->getUrl(
                $childProduct->getData($imageAttributes[ 'type' ]),
                $swatchImageId
            );
        }

        return '';
    }

    private function isProductHasImage(\Magento\Catalog\Model\Product $product, string $imageType): bool
    {
        return $product->getData($imageType) !== null &&
            $product->getData($imageType) != \Magento\Swatches\Helper\Data::EMPTY_IMAGE_VALUE;
    }

    public function getMediaCallback(): string
    {
        return $this->getUrl(
            Configurable::MEDIA_CALLBACK_ACTION,
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }

    public function getJsonSwatchSizeConfig(): ?string
    {
        $imageConfig = $this->swatchMediaHelper->getImageConfig();

        $sizeConfig = [];

        $sizeConfig[ Configurable::SWATCH_IMAGE_NAME ][ 'width' ] =
            $imageConfig[ Swatch::SWATCH_IMAGE_NAME ][ 'width' ];
        $sizeConfig[ Configurable::SWATCH_IMAGE_NAME ][ 'height' ] =
            $imageConfig[ Swatch::SWATCH_IMAGE_NAME ][ 'height' ];
        $sizeConfig[ Configurable::SWATCH_THUMBNAIL_NAME ][ 'height' ] =
            $imageConfig[ Swatch::SWATCH_THUMBNAIL_NAME ][ 'height' ];
        $sizeConfig[ Configurable::SWATCH_THUMBNAIL_NAME ][ 'width' ] =
            $imageConfig[ Swatch::SWATCH_THUMBNAIL_NAME ][ 'width' ];

        return $this->json->encode($sizeConfig);
    }

    public function getShowSwatchTooltip(): bool
    {
        return boolval(
            $this->storeHelper->getStoreConfigFlag(
                'catalog/frontend/show_swatch_tooltip',
                true
            )
        );
    }
}
