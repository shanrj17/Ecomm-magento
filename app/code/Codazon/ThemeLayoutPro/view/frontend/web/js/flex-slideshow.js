define(['jquery', 'jquery-ui-modules/widget', 'owlslider', 'themecore'], function($) {
    $.widget('codazon.flexSlideshow', {
        _create: function() {
            this.$slides = this.element.find('.sls-inner');
            this.itemsCount = this.$slides.children().length;
            this._buildSlider();
        },
        _buildSlider: function() {
            var slCf = this.options.sliderConfig, screens = [1200, 992, 768, 576, 0];
            slCf.items = 1;
            slCf.margin = parseInt(slCf.margin);
            slCf.rtl = ($('body').css('direction') == 'rtl');
            slCf.responsive  = {};
            if (this.itemsCount == 1) {
                slCf.pullDrag = slCf.nav = slCf.dots = 0;
            }
            $.each(screens, (i, scr) => {
                slCf.responsive[scr] = {items: parseFloat(this.element.css('--items-' + scr)), margin: (scr < 768) ? 10 : slCf.margin } ;
            });
            this.element.addClass('loaded');
            this.$slides.addClass('owl-carousel').owlCarousel(slCf);
        }
    });
    return $.codazon.flexSlideshow;
});