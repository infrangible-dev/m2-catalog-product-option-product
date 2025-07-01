/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'domReady',
    'Magento_Swatches/js/swatch-renderer'
], function ($, domReady) {
    'use strict';

    $.widget('mage.productOptionSwatches', $.mage.SwatchRenderer, {
        _init: function ($this, $widget) {
            this._super($this, $widget);

            var self = this;

            domReady(function() {
                $('.swatch-opt .swatch-input').on('change', function() {
                    self.processOptionAttributeMapping($(this));
                });

                $('.swatch-opt .swatch-input').each(function() {
                    self.processOptionAttributeMapping($(this));
                });
            });
        },

        _OnClick: function ($this, $widget) {
            this._super($this, $widget);

            this.updateSelection($this);
        },

        _RenderControls: function($this, $widget) {
            this._super($this, $widget);

            var self = this;
            var container = this.element;

            $.each(self.options.jsonConfig.attributes, function () {
                var item = this;

                var attributeElement = container.find('[data-attribute-code="' + item.code + '"]');
                var attributeAriaAttribute = attributeElement.find('div');

                $(attributeAriaAttribute).attr('aria-required', false);

                $(attributeAriaAttribute).find('div.swatch-option').each(function() {
                    $(this).attr('title', $(this).data('option-label'));
                });
            });

            this._EmulateSelectedByAttributeId(self.options.jsonConfig.optionAttributePreselects);

            container.find('select.catalog-product-option-product-attribute').parent().parent().find('.' + self.options.classes.attributeInput).on('change', function() {
                self.updateSelection($(this));
            });
        },

        _RenderFormInput: function($this, $widget) {
            var formInputElement = this._super($this, $widget);

            var formInput = $(formInputElement);
            formInput.attr('data-validate', '{required: false}');
            formInput.attr('aria-required', false);

            return formInput[0].outerHTML;
        },

        _RenderSwatchSelect: function($this, $widget) {
            var selectHtml = this._super($this, $widget);

            if (selectHtml !== '') {
                var select = $(selectHtml);

                select.addClass('catalog-product-option-product-attribute');

                select.children().each(function() {
                    var option = $(this);

                    if (option.attr('data-option-id') === undefined) {
                        option.attr('data-option-id', option.attr('option-id'));
                    }
                });

                selectHtml = select[0].outerHTML;
            }

            return selectHtml;
        },

        _UpdatePrice: function() {
        },

        _loadMedia: function() {
        },

        updateSelection: function(element) {
            var fieldWrapper = element.closest('.field-wrapper');
            var optionId = fieldWrapper.data('option-id');

            var optionValue = fieldWrapper.find('#checkbox_options_' + optionId);
            var currentProductId = optionValue.val();
            var productId = this.getProductId();

            if (currentProductId !== productId) {
                optionValue.prop('checked', false);
                optionValue.trigger('change');

                optionValue.val(productId);
                optionValue.prop('checked', !!productId);
                optionValue.trigger('change');
            }
        },

        processOptionAttributeMapping: function(swatchInput) {
            var self = this;

            var productSwatches = $(self.element);

            var selectedAttribute = swatchInput.parent();
            var attributeId = parseInt(selectedAttribute.attr('data-attribute-id'));
            var attributeOptionId = parseInt(selectedAttribute.attr('data-option-selected'));

            if (! attributeId || ! attributeOptionId) {
                return;
            }

            $.each(self.options.jsonConfig.optionAttributeMappings, function(sourceAttributeId, sourceAttributeOptionIds) {
                if (parseInt(sourceAttributeId) === attributeId) {
                    $.each(sourceAttributeOptionIds, function(sourceAttributeOptionId, targetAttributeData) {
                        if (parseInt(sourceAttributeOptionId) === attributeOptionId) {
                            $.each(targetAttributeData, function(targetAttributeId, targetAttributeOptionIds) {
                                $.each(targetAttributeOptionIds, function(key, targetAttributeOptionId) {
                                    var swatchAttribute = productSwatches.find(
                                        'div[data-attribute-id=' + targetAttributeId + '] div[data-option-id=' + targetAttributeOptionId + ']');

                                    if (swatchAttribute.length > 0) {
                                        swatchAttribute.click();
                                    } else {
                                        var swatchSelect = productSwatches.find(
                                            '.swatch-attribute[data-attribute-id=' + targetAttributeId + '] select.swatch-select');

                                        if (swatchSelect.length > 0) {
                                            swatchSelect.find('option').removeAttr('selected');

                                            var swatchAttributeOption = swatchSelect.find(
                                                'option[data-option-id=' + targetAttributeOptionId + ']');

                                            if (swatchAttributeOption.length > 0) {
                                                swatchAttributeOption.attr('selected', true);
                                                swatchAttributeOption.trigger('change');
                                            } else {
                                                swatchAttributeOption = swatchSelect.find(
                                                    'option[option-id=' + targetAttributeOptionId + ']');

                                                if (swatchAttributeOption.length > 0) {
                                                    swatchAttributeOption.attr('selected', true);
                                                    swatchAttributeOption.trigger('change');
                                                }
                                            }
                                        }
                                    }
                                });
                            });
                        }
                    });
                }
            });
        }
    });

    return $.mage.productOptionSwatches;
});
