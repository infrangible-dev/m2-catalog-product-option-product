/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'Magento_Swatches/js/swatch-renderer'
], function ($) {
    'use strict';

    $.widget('mage.productOptionSwatches', $.mage.SwatchRenderer, {
        _OnClick: function ($this, $widget) {
            this._super($this, $widget);

            var fieldWrapper = $this.closest('.field-wrapper');
            var optionId = fieldWrapper.data('option-id');

            var optionValue = fieldWrapper.find('#checkbox_options_' + optionId);
            var productId = this.getProductId();

            if (optionValue.val() !== productId) {
                optionValue.prop('checked', false);
                optionValue.trigger('change');

                optionValue.val(productId);
                optionValue.prop('checked', !!productId);
                optionValue.trigger('change');
            }
        },

        _UpdatePrice: function () {
        },

        _loadMedia: function () {
        }
    });

    return $.mage.productOptionSwatches;
});
