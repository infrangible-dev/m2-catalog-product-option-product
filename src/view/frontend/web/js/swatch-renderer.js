/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'domReady',
    'Magento_Swatches/js/swatch-renderer'
], function ($, domReady) {
    'use strict';

    return $.widget('mage.productOptionSwatches', $.mage.SwatchRenderer, {
        options: {
            jsonConfig: {
                attributes: [],
                optionAttributeMappings: {},
                initOptionAttributeMappings: true,
                optionAttributePreselects: {},
                initOptionAttributePreselects: true
            }
        },

        _init: function ($this, $widget) {
            this._super($this, $widget);

            const self = this;

            domReady(function () {
                $('.swatch-opt .swatch-input').on('change', function () {
                    self.processOptionAttributeMappings($(this));
                });

                self.element.closest('.control').on('catalog-option-qty-init', function () {
                    self.initAttributes(false);
                });
            });
        },

        _OnClick: function ($this, $widget) {
            this._super($this, $widget);

            this.updateSelection($this);
        },

        _RenderControls: function ($this, $widget) {
            this._super($this, $widget);

            const self = this,
                container = this.element;

            $.each(self.options.jsonConfig.attributes, function () {
                const item = this,
                    attributeElement = container.find('[data-attribute-code="' + item.code + '"]'),
                    attributeAriaAttribute = attributeElement.find('div');

                $(attributeAriaAttribute).attr('aria-required', false);

                $(attributeAriaAttribute).find('div.swatch-option').each(function () {
                    $(this).attr('title', $(this).data('option-label'));
                });
            });

            self.initAttributes(true);

            container.find('select.catalog-product-option-product-attribute')
                .parent()
                .parent()
                .find('.' + self.options.classes.attributeInput)
                .on('change', function () {
                    self.updateSelection($(this));
                });
        },

        initAttributes: function (isInit) {
            const self = this;

            if (!isInit || self.options.jsonConfig.initOptionAttributePreselects) {
                self.processOptionAttributePreselects();
            }

            if (!isInit || self.options.jsonConfig.initOptionAttributeMappings) {
                $('.swatch-opt .swatch-input').each(function () {
                    self.processOptionAttributeMappings($(this));
                });
            }
        },

        _RenderFormInput: function ($this, $widget) {
            const formInputElement = this._super($this, $widget),
                formInput = $(formInputElement);

            formInput.attr('data-validate', '{required: false}');
            formInput.attr('aria-required', false);

            return formInput[0].outerHTML;
        },

        _RenderSwatchSelect: function ($this, $widget) {
            let selectHtml = this._super($this, $widget);

            if (selectHtml !== '') {
                const select = $(selectHtml);

                select.addClass('catalog-product-option-product-attribute');

                select.children().each(function () {
                    const option = $(this);

                    if (option.attr('data-option-id') === undefined) {
                        option.attr('data-option-id', option.attr('option-id'));
                    }
                });

                selectHtml = select[0].outerHTML;
            }

            return selectHtml;
        },

        _UpdatePrice: function () {
        },

        _loadMedia: function () {
        },

        updateSelection: function (element) {
            const fieldWrapper = element.closest('.field-wrapper'),
                optionId = fieldWrapper.data('option-id'),
                optionValue = fieldWrapper.find('#checkbox_options_' + optionId),
                currentProductId = optionValue.val(),
                productId = this.getProductId();

            if (currentProductId !== productId) {
                optionValue.prop('checked', false);
                optionValue.trigger('change');

                optionValue.val(productId);
                optionValue.prop('checked', !!productId);
                optionValue.trigger('change');
            }
        },

        processOptionAttributePreselects: function () {
            const self = this;

            $.each(self.options.jsonConfig.optionAttributePreselects, function (attributeId, attributeOptionId) {
                if (attributeOptionId === 0) {
                    $.each(self.options.jsonConfig.attributes, function (key, attributeData) {
                        if (attributeData.id === attributeId) {
                            const firstAttributeOption = attributeData.options[0];

                            self.options.jsonConfig.optionAttributePreselects[attributeId] = firstAttributeOption.id;
                        }
                    });
                }
            });

            self._EmulateSelectedByAttributeId(self.options.jsonConfig.optionAttributePreselects);
        },

        processOptionAttributeMappings: function (swatchInput) {
            const self = this,
                selectedAttribute = swatchInput.parent(),
                attributeId = parseInt(selectedAttribute.attr('data-attribute-id'), 10),
                attributeOptionId = parseInt(selectedAttribute.attr('data-option-selected'), 10),
                optionAttributeMappings = self.options.jsonConfig.optionAttributeMappings;

            if (!attributeId || !attributeOptionId) {
                return;
            }

            $.each(optionAttributeMappings, function (sourceAttributeId, sourceAttributeOptionIds) {
                if (parseInt(sourceAttributeId, 10) !== attributeId) {
                    return;
                }

                $.each(sourceAttributeOptionIds, function (sourceAttributeOptionId, targetAttributeData) {
                    if (parseInt(sourceAttributeOptionId, 10) === attributeOptionId) {
                        self.selectMappedAttributeOptions(targetAttributeData);
                    }
                });
            });
        },

        /**
         * Selects the options that the selected option of another attribute maps to.
         *
         * @param {Object<string, Array<string>>} targetAttributeData the option IDs by attribute ID
         */
        selectMappedAttributeOptions: function (targetAttributeData) {
            const self = this;

            $.each(targetAttributeData, function (targetAttributeId, targetAttributeOptionIds) {
                $.each(targetAttributeOptionIds, function (key, targetAttributeOptionId) {
                    self.selectMappedAttributeOption(targetAttributeId, targetAttributeOptionId);
                });
            });
        },

        selectMappedAttributeOption: function (targetAttributeId, targetAttributeOptionId) {
            const productSwatches = $(this.element),
                swatchAttribute = productSwatches.find(
                    'div[data-attribute-id=' + targetAttributeId + '] ' +
                    'div[data-option-id=' + targetAttributeOptionId + ']'
                ),
                swatchSelect = productSwatches.find(
                    '.swatch-attribute[data-attribute-id=' + targetAttributeId + '] select.swatch-select'
                );

            if (swatchAttribute.length > 0) {
                this.selectMappedSwatchOption(swatchAttribute);
            } else if (swatchSelect.length > 0) {
                this.selectMappedSelectOption(swatchSelect, targetAttributeOptionId);
            }
        },

        /**
         * Selects an option of a swatch attribute; a disabled one after resetting the selection, if it is enabled then.
         *
         * @param {jQuery} swatchAttribute
         */
        selectMappedSwatchOption: function (swatchAttribute) {
            if (swatchAttribute.hasClass('disabled')) {
                this.resetSelection();

                if (!swatchAttribute.hasClass('disabled')) {
                    swatchAttribute.trigger('click');
                }
            } else if (!swatchAttribute.hasClass('selected')) {
                swatchAttribute.trigger('click');
            }
        },

        /**
         * Selects an option of a swatch attribute that is shown as select; for a disabled one the selection is reset
         * and the first enabled swatch option is selected.
         *
         * @param {jQuery} swatchSelect
         * @param {string} targetAttributeOptionId
         */
        selectMappedSelectOption: function (swatchSelect, targetAttributeOptionId) {
            const swatchAttributeOption = swatchSelect.find('option[data-option-id=' + targetAttributeOptionId + ']');
            let firstEnabledSwatchAttributeOption;

            swatchSelect.find('option').removeAttr('selected');

            if (swatchAttributeOption.length === 0) {
                this.selectOption(swatchSelect.find('option[option-id=' + targetAttributeOptionId + ']'));

                return;
            }

            if (!swatchAttributeOption.is(':disabled')) {
                this.selectOption(swatchAttributeOption);

                return;
            }

            this.resetSelection();

            if (!swatchAttributeOption.is(':disabled')) {
                this.selectOption(swatchAttributeOption);
            }

            firstEnabledSwatchAttributeOption = $(this.element).find('.swatch-option:not([disabled="disabled"]):first');

            if (firstEnabledSwatchAttributeOption.length > 0) {
                firstEnabledSwatchAttributeOption.trigger('click');
                this.selectOption(firstEnabledSwatchAttributeOption);
            }
        },

        resetSelection: function () {
            const productSwatches = $(this.element);

            productSwatches.find('.swatch-option.selected').each(function () {
                $(this).trigger('click');
            });

            productSwatches.find('.swatch-attribute select').each(function () {
                $(this).val('');
                $(this).find('option[data-option-id="0"]').attr('selected', 'selected');
                $(this).trigger('change');
            });
        },

        selectOption: function (option) {
            if (option.length > 0) {
                option.attr('selected', true);
                option.trigger('change');
            }
        }
    });
});
