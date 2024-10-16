<?php
/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeLayoutPro\Block\Adminhtml\MainContent\Edit;

class ContentBuilder extends \Magento\Backend\Block\Template
{
    protected $_assetRepo;
	protected $_itemTypes = false;
    protected $_boostrapCols = false;
    protected $_object = false;
    protected $_resItemsPerRowFds;
    protected $_template = 'Codazon_ThemeLayoutPro::main-content/builder.phtml';
    
    public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		array $data = [])
    {
		$this->_assetRepo = $context->getAssetRepository();
        parent::__construct($context, $data);
    }
    
    public function getResponsiveItemsPerRowFields($default = [1,1,1,1,1])
    {
        if ($this->_resItemsPerRowFds === null) {
            $screens = [1200,992,768,576,0];
            $this->_resItemsPerRowFds = [];
            foreach ($screens as $i => $screen) {
                $this->_resItemsPerRowFds[] = ['type' => 'text', 'name' => 'items-' . $screen, 'label' => 'Screen ≥ ' . $screen . 'px', 'value' => $default[$i]];
            }
        }
        return $this->_resItemsPerRowFds;
    }
    public function getItemTypes()
    {
        if (!$this->hasData('item_types')) {
            $this->_itemTypes = [];
            $this->_itemTypes['separator_general'] = [
                'name'      => 'separator',
                'title'     => __('General')
            ];
            $this->_itemTypes['container'] = [
                'name'      => 'container',
                'title'     => __('Container'),
                'fields'    => [
                    ['type' => 'select', 'name' => 'container_type', 'label' => 'Type', 'attache_header' => true, 'values' => [
                        ['value' => 'box',  'label' => __('Has Margins')],
                        ['value' => 'full', 'label' => __('Full Width')]
                    ]],
                    ['type' => 'text', 'name' => 'class',    'label' => __('HTML Class')],
                    ['type' => 'text', 'name' => 'id',       'label' => __('HTML Id')],
                    ['type' => 'text', 'name' => 'style',    'label' => __('CSS Inline')],
                    ['type' => 'image', 'name' => 'background', 'label' => __('Background')],
                    ['type' => 'select',    'name' => 'hide_on',    'label' => __('Hide on'), 'values' => $this->getScreenOptions()],
                    ['type' => 'select',    'name' => 'attach_to_section_menu',    'label' => __('Attach to Section Menu'), 'values' => $this->getYesNoOptions(), 'selected_value' => 0],
                    ['type' => 'text',      'name' => 'section_menu_icon',          'label' => __('Section Menu Icon')],
                    ['type' => 'text',      'name' => 'title',          'label' => __('Section Title')]
                ]
            ];
            $this->_itemTypes['row'] = [
                'name'      => 'row',
                'title'     => __('Row'),
                'custom_class' => 'row',
                'fields'    => [
                    ['type' => 'text', 'name' => 'class',    'label' => __('HTML Class')],
                    ['type' => 'text', 'name' => 'id',       'label' => __('HTML Id')],
                    ['type' => 'text', 'name' => 'style',    'label' => __('CSS Inline')],
                    ['type' => 'image', 'name' => 'background', 'label' => __('Background')],
                    ['type' => 'select',    'name' => 'hide_on',    'label' => __('Hide on'), 'values' => $this->getScreenOptions()]
                ]
            ];
            $this->_itemTypes['col'] = [
                'name'      => 'col',
                'title'     => __('Column'),
                'custom_class' => 'col-sm-6',
                'fields'    => [
                    ['type' => 'text', 'name' => 'class',    'label' => __('HTML Class')],
                    ['type' => 'text', 'name' => 'id',       'label' => __('HTML Id')],
                    ['type' => 'text', 'name' => 'style',    'label' => __('CSS Inline')],
                    ['type' => 'select', 'name' => 'width', 'label' => 'Type', 'values' => $this->getBoostrapCols(), 'attache_header' => true,
                        'selected_value' => 6
                    ],
                    ['type' => 'image', 'name' => 'background', 'label' => __('Background')],
                    ['type' => 'select',    'name' => 'hide_on',    'label' => __('Hide on'), 'values' => $this->getScreenOptions()]
                ]
            ];
            $this->_itemTypes['custom_tag'] = [
                'name'      => 'custom_tag',
                'title'     => __('Custom Tag'),
                'fields'    => [
                    ['type' => 'text', 'name' => 'tag',    'label' => __('Tag name'), 'value' => 'div', 'attache_header' => true],
                    ['type' => 'text', 'name' => 'class',    'label' => __('HTML Class')],
                    ['type' => 'text', 'name' => 'id',       'label' => __('HTML Id')],
                    ['type' => 'text', 'name' => 'style',    'label' => __('CSS Inline')],
                    ['type' => 'image', 'name' => 'background', 'label' => __('Background')],
                    ['type' => 'select',    'name' => 'hide_on',    'label' => __('Hide on'), 'values' => $this->getScreenOptions()]
                ]
            ];
            $this->_itemTypes['html'] = [
                'name'      => 'html',
                'title'     => __('HTML'),
                'disable_children' => true,
                ['type' => 'select',    'name' => 'hide_on',    'label' => __('Hide on'), 'values' => $this->getScreenOptions()],
                'fields'    => [
                    ['type' => 'text', 'name' => 'title',    'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'editor', 'name' => 'content', 'label' => 'Content', 'attache_desc' => true]
                ]
            ];
            $this->_itemTypes['tabs'] = [
                'name'      => 'tabs',
                'title'     => __('Tabs'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'text', 'name' => 'note',   'label' => __('Note'), 'attache_header' => true],
                    ['type' => 'text', 'name' => 'title',   'label' => __('Title')],
                    ['type' => 'text', 'name' => 'desc',    'label' => __('Description')],
                    ['type' => 'text', 'name' => 'id',    'label' => __('HTML Id')],
                    ['type' => 'text', 'name' => 'class',    'label' => __('CSS Class')],
                    ['type' => 'text', 'name' => 'custom_template',    'label' => __('Custom Template')],
                    ['type' => 'multitext', 'name' => 'items',    'label' => __('Tab Items'), 'full_field' => true, 'need_title' => true, 
                        'sub_fields' => [
                            ['type' => 'text',  'name' => 'title',  'label' => __('Title'), 'field_class_prefix' => 'm', 'prefix' => 'm'],
                            ['type' => 'text', 'name' => 'icon',   'label' => __('Icon'),   'field_class_prefix' => 'm', 'prefix' => 'm'],
                            ['type' => 'text', 'name' => 'external_url',  'label' => __('External Url'),  'desc' => __('Open new page when clicking tab link instead of linked tab. Leave empty for tab default action.'), 'field_class_prefix' => 'm', 'prefix' => 'm'],
                            ['type' => 'text', 'name' => 'class',  'label' => __('Custom CSS Class'),  'field_class_prefix' => 'm', 'prefix' => 'm'],
                            ['type' => 'editor', 'name' => 'content',  'label' => __('Tab Content'),  'field_class_prefix' => 'm', 'prefix' => 'm']
                        ]
                    ],
                    ['type' => 'text', 'name' => 'style',    'label' => __('CSS Inline')],
                    ['type' => 'image', 'name' => 'background',    'label' => __('Tabs background')],
                ]
            ];
            $this->_itemTypes['separator_simple_slider'] = [
                'name'      => 'separator',
                'title'     => __('Simple Slider')
            ];
            $this->_itemTypes['html_slider'] = [
                'name'      => 'html_slider',
                'title'     => __('HTML Slider'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'text', 'name' => 'title',   'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'text', 'name' => 'desc',    'label' => __('Description')],
                    ['type' => 'text', 'name' => 'class',    'label' => __('Wrapper Class')],
                    ['type' => 'text', 'name' => 'item_class',    'label' => __('Item Class')],
                    ['type' => 'text', 'name' => 'settings',    'label' => __('Settings'), 'value' => '{"nav":true,"dots":false,"responsive":{"1900":{"items":6},"1600":{"items":5},"1420":{"items":5},"1280":{"items":5},"980":{"items":4},"768":{"items":4},"480":{"items":3.5},"320":{"items":2.5},"0":{"items":1}},"margin":20}', 'desc' => __('Get more options <a href="%1" target="_blank">here</a>.', 'https://owlcarousel2.github.io/OwlCarousel2/docs/api-options.html')],
                    ['type' => 'text', 'name' => 'custom_template',    'label' => __('Custom Template')],
                    ['type' => 'multitext', 'name' => 'items',    'label' => __('Slide Items'), 'full_field' => true,
                        'sub_fields' => [
                            ['type' => 'editor', 'name' => 'content',  'label' => __('Slide HTML')]
                        ]
                    ],
                ]
            ];
            $this->_itemTypes['images_slider'] = [
                'name'      => 'images_slider',
                'title'     => __('Image Slider'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'text', 'name' => 'title',   'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'text', 'name' => 'class',    'label' => __('Wrapper Class')],
                    ['type' => 'text', 'name' => 'item_class',    'label' => __('Item Class')],
                    ['type' => 'text', 'name' => 'settings',    'label' => __('Settings'), 'value' => '{"nav":true,"dots":false,"responsive":{"1900":{"items":6},"1600":{"items":5},"1420":{"items":5},"1280":{"items":5},"980":{"items":4},"768":{"items":4},"480":{"items":3.5},"320":{"items":2.5},"0":{"items":1}},"margin":20}', 'desc' => __('Get more options <a href="%1" target="_blank">here</a>.', 'https://owlcarousel2.github.io/OwlCarousel2/docs/api-options.html')],
                    ['type' => 'text', 'name' => 'custom_template',    'label' => __('Custom Template'), 'desc' => __('Leave empty to use default template')],
                    ['type' => 'multitext', 'name' => 'items',    'label' => __('Slide Items'), 'full_field' => true,
                        'sub_fields' => [
                            ['type' => 'text',  'name' => 'title',      'label' => __('Title')],
                            ['type' => 'text',  'name' => 'link',       'label' => __('Link')],
                            ['type' => 'image', 'name' => 'image',      'label' => __('Image')],
                            ['type' => 'editor', 'name' => 'content',   'label' => __('Description')]
                        ]
                    ],
                ]
            ];
            $this->_itemTypes['slideshow'] = [
                'name'      => 'slideshow',
                'title'     => __('Slideshow'),
                'disable_children' => true,
                'require_css' => [
                    [
                        'name' => 'slideshow/_preview-thumb-dots.less.css',
                        'condition' => [
                            'field'     => 'show_thumb_dots',
                            'operator'  => '==',
                            'value' => '1'
                        ]
                    ],
                    [
                        'name' => 'slideshow/_preview-thumb-nav.less.css',
                        'condition' => [
                            'field'     => 'show_thumb_nav',
                            'operator'  => '==',
                            'value' => '1'
                        ]
                    ],
                ],
                'fields'    => [
                    ['type' => 'text', 'name' => 'title',           'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'multitext', 'name' => 'items',      'label' => __('Slide Items'), 'full_field' => true,
                        'sub_fields' => [
                            ['type' => 'text',  'name' => 'title',      'label' => __('Title')],
                            ['type' => 'text',  'name' => 'link',       'label' => __('Link')],
                            ['type' => 'image', 'name' => 'image',      'label' => __('Image')],
                            ['type' => 'text', 'name' => 'video',       'label' => __('Video URL'), 'desc' => __('Display video from Youtube or Vimeo. Video from Youtube works better')],
                            ['type' => 'text', 'name' => 'video_ratio', 'label' => __('Video Ratio'), 'desc' => __('Ratio = Height/Width (E.g.: 0.5)')],
                            ['type' => 'editor', 'name' => 'content',  'label' => __('Description')],
                        ]
                    ],
                    ['type' => 'text', 'name' => 'class',           'label' => __('Wrapper Class')],
                    ['type' => 'text', 'name' => 'width',           'label' => __('Width (px)')],
                    ['type' => 'text', 'name' => 'height',          'label' => __('Height (px)')],
                    ['type' => 'select', 'name' => 'animation_in',  'label' => __('Animation In'), 'values' => $this->getAnimationsArray(1)],
                    ['type' => 'select', 'name' => 'animation_out', 'label' => __('Animation Out'), 'values' => $this->getAnimationsArray(2)],
                    ['type' => 'select', 'name' => 'show_nav',      'label' => __('Show Arrows'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'show_dots',     'label' => __('Show Dots'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'show_thumb_nav',      'label' => __('Show Preview on Arrows'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'show_thumb_dots',     'label' => __('Show Preview on Dots'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'auto_play',     'label' => __('Auto Play'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'disable_lazy_load',     'label' => __('Disable Lazy Load'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'select', 'name' => 'loop',    'label' => __('Loop'), 'values' => $this->getYesNoOptions()],
                    ['type' => 'text', 'name' => 'auto_play_timeout',   'label' => __('Auto Play Timeout'), 'value' => 5000],
                    ['type' => 'select', 'name' => 'use_org',   'label' => __('Use original images?'), 'values' => $this->getYesNoOptions(), 'selected_value' => 0, 'desc' => __('Use original images instead of resized images')],
                    ['type' => 'text', 'name' => 'custom_template',   'label' => __('Custom Template'), 'desc' => __('Leave empty to use default template')]
                ]
            ];
            $this->_itemTypes['separator_slideshow_container'] = [
                'name'      => 'separator',
                'title'     => __('Flexible Slideshow')
            ];
            $this->_itemTypes['slideshow_container'] = [
                'name'      => 'slideshow_container',
                'title'     => __('Slideshow Container'),
                'fields'    => [
                    ['type' => 'text', 'name' => 'title', 'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'text', 'name' => 'class', 'label' => __('Wrapper Class')],
                    ['type' => 'hidden', 'name' => 'template', 'value' => 'Codazon_ThemeLayoutPro::content-builder/element/slideshow-container.phtml'],
                    ['type' => 'select', 'name' => 'custom_style', 'label' => 'Slideshow Style', 'values' => [
                        ['value' => 'slsh-01', 'label' => 'Style 01'],
                        ['value' => 'slsh-02', 'label' => 'Style 02'],
                    ]],
                    ['type' => 'text', 'name' => 'width', 'label' => __('Width (px)'), 'value' => '1900'],
                    ['type' => 'text', 'name' => 'height', 'label' => __('Height (px)'), 'value' => '900'],
                    ['type' => 'field_group', 'name' => 'slider_config', 'label' => __('Slider configuration'), 'full_field' => true, 'sub_fields' => [
                        ['type' => 'select', 'name' => 'animateIn', 'label' => __('Animation In'), 'values' => $this->getAnimationsArray(1)],
                        ['type' => 'select', 'name' => 'animateOut', 'label' => __('Animation Out'), 'values' => $this->getAnimationsArray(2)],
                        ['type' => 'select', 'name' => 'nav', 'label' => __('Show Arrows'), 'values' => $this->getYesNoOptions()],
                        ['type' => 'select', 'name' => 'dots', 'label' => __('Show Dots'), 'values' => $this->getYesNoOptions()],
                        ['type' => 'select', 'name' => 'autoplay', 'label' => __('Auto Play'), 'values' => $this->getYesNoOptions()],
                        ['type' => 'select', 'name' => 'loop', 'label' => __('Loop'), 'values' => $this->getYesNoOptions()],
                        ['type' => 'text', 'name' => 'autoplayTimeout', 'label' => __('Auto Play Timeout'), 'value' => 5000],
                        ['type' => 'text', 'name' => 'margin', 'label' => __('Item Margin (px)'), 'value' => '0'],
                        ['type' => 'text', 'name' => 'stagePadding', 'label' => __('Stage Padding (px)'), 'value' => '0'],
                        ['type' => 'select', 'name' => 'center', 'label' => __('Center'), 'values' => $this->getYesNoOptions()],
                    ]],
                    ['type' => 'field_group', 'name' => 'responsive', 'label' => __('Responsive'), 'full_field' => true, 'sub_fields' => $this->getResponsiveItemsPerRowFields()],
                    ['type' => 'field_group', 'name' => 'variables', 'label' => __('Customization'), 'full_field' => true, 'sub_fields' => [
                        ['type' => 'color', 'name' => 'nav-bkg', 'label' => __('Arrow Background')],
                        ['type' => 'color', 'name' => 'nav-txt', 'label' => __('Arrow Color')],
                        ['type' => 'color', 'name' => 'nav-bd', 'label' => __('Arrow Border Color')],
                        ['type' => 'text', 'name' => 'nav-w-desk', 'label' => __('Arrow Width')],
                        ['type' => 'text', 'name' => 'nav-h-desk', 'label' => __('Arrow Height')],
                        ['type' => 'text', 'name' => 'nav-bdr', 'label' => __('Arrow Border Radius')],
                        ['type' => 'text', 'name' => 'el-bkf', 'label' => __('Backface Color'), 'desc' => 'E.g.: rgba(0,0,0,30%)'],
                        ['type' => 'text', 'name' => 'el-bkf-hv', 'label' => __('Hovered Backface Color'), 'desc' => 'E.g.: rgba(0,0,0,50%)'],
                        ['type' => 'select', 'name' => 'el-mb-pos-y', 'label' => __('Mobile Position'), 'values' => [
                            ['label' => 'Top', 'value' => 'start'],
                            ['label' => 'Middle', 'value' => 'center'],
                            ['label' => 'Bottom', 'value' => 'end']
                        ]],
                        ['type' => 'color', 'name' => 'el-bkg', 'label' => __('Slide Background')],
                        ['type' => 'color', 'name' => 'el-txt', 'label' => __('Slide Text')],
                    ]],
                    ['type' => 'select', 'name' => 'dots_style', 'label' => 'Dots Style', 'values' => [
                        ['value' => 'sls-dots-01', 'label' => 'Style 01'],
                        ['value' => 'sls-dots-02', 'label' => 'Style 02'],
                    ]],
                    ['type' => 'select', 'name' => 'dots_pos', 'label' => 'Dots Position', 'values' => [
                        ['value' => 'sls-dots-in', 'label' => 'Inside'],
                        ['value' => 'sls-dots-out', 'label' => 'Outside'],
                    ]],
                    ['type' => 'text', 'name' => 'style', 'label' => __('CSS Inline')]
                ]
            ];
            $this->_itemTypes['slideshow_slide'] = [
                'name'      => 'slideshow_slide',
                'title'     => __('Slideshow > Slide'),
                'custom_class' => 'el-prv-img-top',
                'fields'    => [
                    ['type' => 'text', 'name' => 'title', 'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'image', 'name' => 'image', 'label' => __('Slide Image'), 'attach_bkg' => true],
                    ['type' => 'image', 'name' => 'image_mobi', 'label' => __('Mobile Image')],
                    ['type' => 'text', 'name' => 'url', 'label' => __('URL'), 'desc' => __('Optional')],
                    ['type' => 'field_group', 'name' => 'variables', 'label' => __('Customization (Optional)'), 'full_field' => true, 'sub_fields' => [
                        ['type' => 'text', 'name' => 'el-bkf', 'label' => __('Backface color'), 'desc' => 'rgba(0,0,0,30%)'],
                        ['type' => 'text', 'name' => 'el-bkf-hv', 'label' => __('Hovered Backface Color'), 'desc' => 'E.g.: rgba(0,0,0,50%)'],
                        ['type' => 'select', 'name' => 'el-mb-pos-y', 'label' => __('Mobile Position'), 'values' => [
                            ['label' => 'Top', 'value' => 'start'],
                            ['label' => 'Middle', 'value' => 'center'],
                            ['label' => 'Bottom', 'value' => 'end']
                        ]],
                    ]]
                ]
            ];
            $this->_itemTypes['slide_text'] = [
                'name'      => 'slide_element_text',
                'title'     => __('Slide > Text'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'editor', 'name' => 'text', 'label' => __('HTML'), 'attache_desc' => true],
                    ['type' => 'text', 'name' => 'class', 'label' => __('Wrapper class')],
                    ['type' => 'select', 'name' => 'pos_x', 'label' => __('Position X'), 'values' => [
                        ['label' => 'Left', 'value' => 'left'],
                        ['label' => 'Center', 'value' => 'center'],
                        ['label' => 'Right', 'value' => 'right'],
                        ['label' => 'Start', 'value' => 'start'],
                        ['label' => 'End', 'value' => 'end']
                    ]],
                    ['type' => 'select', 'name' => 'pos_y', 'label' => __('Position Y'), 'values' => [
                        ['label' => 'Top', 'value' => 'start'],
                        ['label' => 'Middle', 'value' => 'center'],
                        ['label' => 'Bottom', 'value' => 'end']
                    ]],
                    ['type' => 'field_group', 'name' => 'variables', 'label' => __('Customization'), 'full_field' => true, 'sub_fields' => [
                        ['type' => 'select', 'name' => 'el-txt-al', 'label' => __('Text Align'), 'values' => [
                            ['label' => 'Left', 'value' => 'left'],
                            ['label' => 'Center', 'value' => 'center'],
                            ['label' => 'Right', 'value' => 'right'],
                            ['label' => 'Start', 'value' => 'start'],
                            ['label' => 'End', 'value' => 'end']
                        ]],
                        ['type' => 'select', 'name' => 'el-eff-in',  'label' => __('Animation In'), 'values' => $this->getAnimationsArray(1)],
                        ['type' => 'text', 'name' => 'el-os-x', 'label' => __('Offset X')],
                        ['type' => 'text', 'name' => 'el-os-y', 'label' => __('Offset Y')],
                        ['type' => 'text', 'name' => 'el-ct-w', 'label' => __('Max Width'), 'desc' => '100%, 50%, 600px, 40vw, etc.'],
                        ['type' => 'color', 'name' => 'el-txt', 'label' => __('Slide color')],
                        ['type' => 'color', 'name' => 'el-title-txt', 'label' => __('Title color')],
                        ['type' => 'text', 'name' => 'el-fs', 'label' => __('Text font size')],
                        ['type' => 'text', 'name' => 'el-title-fs', 'label' => __('Title font size')],
                        ['type' => 'text', 'name' => 'el-dsk-pd-x', 'label' => __('Horizontal Padding'), 'desc' => 'Default: 60px'],
                        ['type' => 'text', 'name' => 'el-dsk-pd-y', 'label' => __('Vertical Padding'), 'desc' => 'Default: 60px']
                    ]]
                ]
            ];
            $this->_itemTypes['slide_image'] = [ 
                'name'      => 'slide_element_image',
                'title'     => __('Slide > Image'),
                'custom_class' => 'el-prv-img',
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'image', 'name' => 'image', 'label' => __('Image'), 'attach_bkg' => true],
                    ['type' => 'textarea', 'name' => 'svg', 'label' => __('SVG'), 'desc' => 'You can use SVG code instead of a image file.', 'attache_desc' => true],
                    ['type' => 'url', 'name' => 'URL', 'label' => __('URL')],
                    ['type' => 'text', 'name' => 'class', 'label' => __('Wrapper class')],
                    ['type' => 'select', 'name' => 'pos_x', 'label' => __('Position X'), 'values' => [
                        ['label' => 'Left', 'value' => 'left'],
                        ['label' => 'Center', 'value' => 'center'],
                        ['label' => 'Right', 'value' => 'right']
                    ]],
                    ['type' => 'select', 'name' => 'pos_y', 'label' => __('Position Y'), 'values' => [
                        ['label' => 'Top', 'value' => 'start'],
                        ['label' => 'Middle', 'value' => 'center'],
                        ['label' => 'Bottom', 'value' => 'end']
                    ]],
                    ['type' => 'field_group', 'name' => 'variables', 'label' => __('Customization'), 'full_field' => true, 'sub_fields' => [
                        ['type' => 'select', 'name' => 'el-eff-in',  'label' => __('Animation In'), 'values' => $this->getAnimationsArray(1)],
                        ['type' => 'text', 'name' => 'el-os-x', 'label' => __('Offset X')],
                        ['type' => 'text', 'name' => 'el-os-y', 'label' => __('Offset Y')],
                        ['type' => 'text', 'name' => 'el-ct-w', 'label' => __('Max Width'), 'desc' => '100%, 50%, 600px, 40vw, etc.']
                    ]]
                ]
            ];
             $this->_itemTypes['separator_other'] = [
                'name'      => 'separator',
                'title'     => __('Other')
            ];
            $this->_itemTypes['video'] = [
                'name'      => 'video',
                'title'     => __('Video Frame'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'text',      'name' => 'title',              'label' => __('Title'), 'attache_header' => true],
                    ['type' => 'image',     'name' => 'placeholder',        'label' => __('Placehoder Image')],
                    ['type' => 'select',    'name' => 'use_df_placeholder', 'label' => __('Use Default Placehoder'), 'values' => $this->getYesNoOptions(), 'selected_value' => 1,
                        'desc' => __('Default Placehoder is loaded from Youtube or Vimeo')],
                    ['type' => 'text',      'name' => 'video_url',          'label' => __('Video URL'), 'desc' => __('Get Video from Youtube or Vimeo')],
                    ['type' => 'text',      'name' => 'ratio',              'label' => __('Frame Dimension Ratio '), 
                        'desc' => __('Ratio = Height/Width. Eg. 480px/854px = 0.562')]
                ]
            ];
            $this->_itemTypes['custom_script'] = [
                'name'      => 'custom_script',
                'title'     => __('Custom Script'),
                'disable_children' => true,
                'fields'    => [
                    ['type' => 'textarea', 'name' => 'script', 'label' => __('Script')]
                ]
            ];
            
            $this->setData('item_types', $this->_itemTypes);
            $this->_eventManager->dispatch(
				'content_builder_init_item_types_after',
				['builder' => $this]
			);
        }
        return $this->getData('item_types');
    }
    
    public function getYesNoOptions()
    {
        return [
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Yes')]
        ];
    }
    
    public function getScreenOptions()
    {
        return array(
            array('label' => '-', 'value' => ''),
            array('label' => __('Desktop and Mobile'), 'value' => 'hidden'),
            array('label' => __('Desktop'), 'value' => 'visible-xs'),
            array('label' => __('Mobile'),  'value' => 'hidden-xs')
        );
    }
    
    public function getAnimationsArray($type = 0)
    {
        $animations = array(
            array('label' => '-- none animation --', 'value' => ''),
            array('label' => 'bounce', 'value' => 'bounce'),
            array('label' => 'flash', 'value' => 'flash'),
            array('label' => 'pulse', 'value' => 'pulse'),
            array('label' => 'rubberBand', 'value' => 'rubberBand'),
            array('label' => 'shake', 'value' => 'shake'),
            array('label' => 'swing', 'value' => 'swing'),
            array('label' => 'tada', 'value' => 'tada'),
            array('label' => 'wobble', 'value' => 'wobble'),
            array('label' => 'jello', 'value' => 'jello'),
            array('label' => 'bounceIn', 'value' => 'bounceIn'),
            array('label' => 'bounceInDown', 'value' => 'bounceInDown'),
            array('label' => 'bounceInLeft', 'value' => 'bounceInLeft'),
            array('label' => 'bounceInRight', 'value' => 'bounceInRight'),
            array('label' => 'bounceInUp', 'value' => 'bounceInUp'),
            array('label' => 'bounceOut', 'value' => 'bounceOut'),
            array('label' => 'bounceOutDown', 'value' => 'bounceOutDown'),
            array('label' => 'bounceOutLeft', 'value' => 'bounceOutLeft'),
            array('label' => 'bounceOutRight', 'value' => 'bounceOutRight'),
            array('label' => 'bounceOutUp', 'value' => 'bounceOutUp'),
            array('label' => 'fadeIn', 'value' => 'fadeIn'),
            array('label' => 'fadeInDown', 'value' => 'fadeInDown'),
            array('label' => 'fadeInDownBig', 'value' => 'fadeInDownBig'),
            array('label' => 'fadeInLeft', 'value' => 'fadeInLeft'),
            array('label' => 'fadeInLeftBig', 'value' => 'fadeInLeftBig'),
            array('label' => 'fadeInRight', 'value' => 'fadeInRight'),
            array('label' => 'fadeInRightBig', 'value' => 'fadeInRightBig'),
            array('label' => 'fadeInUp', 'value' => 'fadeInUp'),
            array('label' => 'fadeInUpBig', 'value' => 'fadeInUpBig'),
            array('label' => 'fadeOut', 'value' => 'fadeOut'),
            array('label' => 'fadeOutDown', 'value' => 'fadeOutDown'),
            array('label' => 'fadeOutDownBig', 'value' => 'fadeOutDownBig'),
            array('label' => 'fadeOutLeft', 'value' => 'fadeOutLeft'),
            array('label' => 'fadeOutLeftBig', 'value' => 'fadeOutLeftBig'),
            array('label' => 'fadeOutRight', 'value' => 'fadeOutRight'),
            array('label' => 'fadeOutRightBig', 'value' => 'fadeOutRightBig'),
            array('label' => 'fadeOutUp', 'value' => 'fadeOutUp'),
            array('label' => 'fadeOutUpBig', 'value' => 'fadeOutUpBig'),
            array('label' => 'flip', 'value' => 'flip'),
            array('label' => 'flipInX', 'value' => 'flipInX'),
            array('label' => 'flipInY', 'value' => 'flipInY'),
            array('label' => 'flipOutX', 'value' => 'flipOutX'),
            array('label' => 'flipOutY', 'value' => 'flipOutY'),
            array('label' => 'lightSpeedIn', 'value' => 'lightSpeedIn'),
            array('label' => 'lightSpeedOut', 'value' => 'lightSpeedOut'),
            array('label' => 'rotateIn', 'value' => 'rotateIn'),
            array('label' => 'rotateInDownLeft', 'value' => 'rotateInDownLeft'),
            array('label' => 'rotateInDownRight', 'value' => 'rotateInDownRight'),
            array('label' => 'rotateInUpLeft', 'value' => 'rotateInUpLeft'),
            array('label' => 'rotateInUpRight', 'value' => 'rotateInUpRight'),
            array('label' => 'rotateOut', 'value' => 'rotateOut'),
            array('label' => 'rotateOutDownLeft', 'value' => 'rotateOutDownLeft'),
            array('label' => 'rotateOutDownRight', 'value' => 'rotateOutDownRight'),
            array('label' => 'rotateOutUpLeft', 'value' => 'rotateOutUpLeft'),
            array('label' => 'rotateOutUpRight', 'value' => 'rotateOutUpRight'),
            array('label' => 'slideInUp', 'value' => 'slideInUp'),
            array('label' => 'slideInDown', 'value' => 'slideInDown'),
            array('label' => 'slideInLeft', 'value' => 'slideInLeft'),
            array('label' => 'slideInRight', 'value' => 'slideInRight'),
            array('label' => 'slideOutUp', 'value' => 'slideOutUp'),
            array('label' => 'slideOutDown', 'value' => 'slideOutDown'),
            array('label' => 'slideOutLeft', 'value' => 'slideOutLeft'),
            array('label' => 'slideOutRight', 'value' => 'slideOutRight'),
            array('label' => 'zoomIn', 'value' => 'zoomIn'),
            array('label' => 'zoomInDown', 'value' => 'zoomInDown'),
            array('label' => 'zoomInLeft', 'value' => 'zoomInLeft'),
            array('label' => 'zoomInRight', 'value' => 'zoomInRight'),
            array('label' => 'zoomInUp', 'value' => 'zoomInUp'),
            array('label' => 'zoomOut', 'value' => 'zoomOut'),
            array('label' => 'zoomOutDown', 'value' => 'zoomOutDown'),
            array('label' => 'zoomOutLeft', 'value' => 'zoomOutLeft'),
            array('label' => 'zoomOutRight', 'value' => 'zoomOutRight'),
            array('label' => 'zoomOutUp', 'value' => 'zoomOutUp'),
            array('label' => 'hinge', 'value' => 'hinge'),
            array('label' => 'rollIn', 'value' => 'rollIn'),
            array('label' => 'rollOut', 'value' => 'rollOut')
        );
        if ($type === 1) {
            foreach ($animations as $i => $animation) {
                if ((strpos($animation['value'], 'Out') !== false) && ($animation['value'] != '')) { //stripos not differentiate uppercase/lowercase
                    unset($animations[$i]);
                }
            }
        } elseif ($type === 2) {
            foreach ($animations as $i => $animation) {
                if ((strpos($animation['value'], 'Out') === false) && ($animation['value'] != '')) { //stripos not differentiate uppercase/lowercase
                    unset($animations[$i]);
                }
            }
        }
        return $animations;
    }
    
    public function getBoostrapCols()
    {
        if ($this->_boostrapCols === false) {
            $this->_boostrapCols = [];
            for($i=1; $i <= 24; $i++) {
                $this->_boostrapCols[$i] = [
                    'value' => $i,
                    'label' => 'col-sm-' . $i
                ];
            }
        }
        return $this->_boostrapCols;
    }
    
    public function getImageUrl($path)
    {
        return $this->_assetRepo->getUrl('Codazon_ThemeLayoutPro/images/'.$path);
    }
    
    public function getMediaUrl($path = '')
    {
		return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$path;
	}
    
    public function getDataObject()
    {
        if ($this->_object === false) {
            $this->_object = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Registry')->registry('themelayout_maincontent');
        }
        return $this->_object;
    }
    
    public function displayUseDefault($attributeCode)
    {
        $store = $this->getRequest()->getParam('store');
        return ($store != \Magento\Store\Model\Store::DEFAULT_STORE_ID);
    }
    
    public function isUseDefault($attributeCode)
    {
        $object = $this->getDataObject();
        return ($object->getExistsStoreValueFlag('themelayout_content') != 1);
    }
    
    public function getPreviewImage()
    {
        if ($assetDir = $this->getData('asset_directory')) {
            $registryName = $this->getData('registry_name');
            $registryModel = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry')->registry($registryName);
            if ($registryModel) {
                $directory = $registryModel->getIdentifier();
                if ($directory) {
                    $file = $assetDir.'/'.$directory.'/preview.jpg';
                    if ($registryModel->mediaFileExists($file, true)) {
                        return $this->getMediaUrl($file);
                    }
                }
            }
        }
        return false;
    }
}