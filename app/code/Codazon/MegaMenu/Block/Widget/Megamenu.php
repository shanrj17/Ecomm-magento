<?php
/**
 * Copyright © 2016 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\MegaMenu\Block\Widget;
use Magento\Framework\View\Element\Template;
use Codazon\MegaMenu\Block\Widget\CategoriesTree;
use Codazon\MegaMenu\Model\MegamenuFactory as MegamenuFactory;

class Megamenu extends Template implements \Magento\Widget\Block\BlockInterface
{
	protected $_megamenuFactory;
	protected $_categoriesTree;
	protected $_filterProvider;
	protected $_storeManager;
	protected $_blockFactory;
	protected $_blockFilter;
	protected $_menuObject;
	protected $_menuContentArray;
	protected $_menuTree = null;
	protected $_categoryTree = null;
	
	public function __construct(
		Template\Context $context,
		MegamenuFactory $megamenuFactory,
		\Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory,
		\Magento\Framework\App\Http\Context $httpContext,
        array $data = []
	){
		parent::__construct($context, $data);
		$this->_megamenuFactory = $megamenuFactory;
		$this->httpContext = $httpContext;
		$this->_filterProvider = $filterProvider;
        $this->_storeManager = $context->getStoreManager();
        $this->_blockFactory = $blockFactory;
		$storeId = $this->_storeManager->getStore()->getId();
		$this->_blockFilter = $this->_filterProvider->getBlockFilter()->setStoreId($storeId);
		$this->setData('need_filter',true);
		if($this->getData('custom_template')){
			$this->setTemplate($this->getData('custom_template'));
		}else{
			$this->setTemplate('menu.phtml');
		}
		$this->addData([
            'cache_lifetime' => 86400,
            'cache_tags' => ['MEGAMENU']
		]);
	}
	
    public function getMenuTree()
    {
		if($this->_menuTree === null){
			$menu = $this->getMenuObject();
            if (!$menu) {
                $this->_menuTree = false;
                return $this->_menuTree;
            }
			if(!($menu->getContent())){
				$this->_menuTree = false;
				return $this->_menuTree;
			}
			if($menu){
				$items = json_decode($menu->getContent());
				$tree = array();
				$i = 0;
				$buff = array();
				if(count($items)){
						foreach($items as $item){
							$current = &$buff[$i];
							$current = $item;
							$k = $i - 1;
							while(isset($items[$k]) && ($k > 0)){
								if(($items[$k]->depth < $items[$i]->depth)){
									break;
								}else{
									$k--;
								}
							}
							if($item->depth == 0){
								$tree[$i] = &$current;
							}else{
								$buff[$k]->children[$i] = &$current;
							}
							$i++;
						}
				}
				$this->_menuTree = $tree;
			}
		}
		return $this->_menuTree;
	}
	
    public function getMenuObject()
    {
		if (!$this->_menuObject) {
			$identifier = trim((string)$this->getMenu());
			$megamenu = $this->_megamenuFactory->create();		
			$col = $megamenu->getCollection()
				->addFieldToFilter('is_active', 1)
				->addFieldToFilter('identifier', $identifier);
			if ($col->count() > 0) {
				$this->_menuObject = $col->getFirstItem();
			} else {
				$this->_menuObject = false;
			}
		}
		return $this->_menuObject;
	}
    
    public function getTagHtml($content)
    {
        if (!empty($content->tag)) {
            $css = [];
            if ($content->tag_color) {
                $css[] = 'color:'. $content->tag_color;
            }
            if ($content->tag_background) {
                $css[] = 'background:'. $content->tag_background;
                $css[] = 'border-color:'. $content->tag_background;
            }
            return '<span class="cdz-item-tag" '. ($css? 'style="' . implode(';', $css) . '"' : '') .'>' . $content->tag . '</span>';
        }
        return '';
    }
    
    public function getCustomStyle($content)
    {
        $css = [];
        if (!empty($content->title_color)) {
            $css[] = 'color: '. $content->title_color;
        }
        if (!empty($content->title_background)) {
            $css[] = 'background: '. $content->title_background;
        }
        return $css ? 'style="'. implode(';', $css) . '"' : '';
    }
    
	public function setMenuObject($menuObject)
    {
		$this->_menuObject = $menuObject;
		return $this;
	}
	
    protected function _toHtml()
    {
        return $this->getData('need_filter') ? $this->filter(parent::_toHtml()) : parent::_toHtml();
    }
	
    public function getMenuHtml($items)
    {
		$html = '';
		foreach($items as $key => $item){
			$html .= $this->addData(['current_item' => $item])->_toHtml();
		}
		return $html;
	}
	
	public function getIcon($content)
    {
        if (!empty($content->icon_svg)) {
            return '<i class="menu-icon i-svg img-icon">'.$content->icon_svg.'</i>';
        } else {
            if(isset($content->icon_type) && $content->icon_type == 0){
                return ($content->icon_font)?'<i class="menu-icon fa fa-'.$content->icon_font.'"></i>':'';	
            }else{
                return ($content->icon_img)?'<i class="menu-icon img-icon"><img icon-img alt="'.$this->escapeHtml($content->label).'" src="'.$content->icon_img.'"></i>':'';	
            }
            return '';
        }
	}
	
	public function filter($content)
    {
        return preg_replace_callback(
            '/(<img[^>]+>(?:<\/img>)?)/i',
            function ($matches)
            {
                return (stripos($matches[0], 'icon-img') === false) ? str_replace(' src=', ' src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=" data-menulazy=', $matches[0]) : $matches[0];
            },
            $this->_blockFilter->filter($content)
        );
	}
	
	public function hasChildren($items,$i){
		$curDepth = $items[$i]->depth;
		$nextDepth = isset($items[$i+1])?$items[$i+1]->depth:$curDepth;
		return ($nextDepth > $curDepth);
	}
    
	public function getBackgroundStyle($content){
		switch ($content->bg_position){
			case 'left_top':
				return "left:{$content->bg_position_x}px; top:{$content->bg_position_y}px"; break;
			case 'left_bottom':
				return "left:{$content->bg_position_x}px; bottom:{$content->bg_position_y}px"; break;
			case 'right_top':
				return "right:{$content->bg_position_x}px; top:{$content->bg_position_y}px"; break;
			case 'right_bottom':
			default:
				return "right:{$content->bg_position_x}px; bottom:{$content->bg_position_y}px"; break;
		}
	}
	
    public function getMenuContentArray(){
		if (!$this->_menuContentArray) {
			$this->_menuContentArray = json_decode($this->getMenuObject()->getContent());
		}
		return $this->_menuContentArray;
	}
    
	public function getItemCSSClass($item)
	{
		$depth = (int)$item->depth;
		$content = $item->content;
		$class[] = "item level{$depth} {$content->class}";
		if ($depth == 0) {
			$class[] = 'level-top';
		}
		switch ($item->item_type) {
			case 'category':
				$class[] = 'parent cat-tree no-full';
				if($content->display_type == 1){
					$class[] = 'no-dropdown';
				}
				break;
			case 'link':
				if(isset($item->children)){
					$class[] = 'parent';
				}
				break;
			case 'text':
				$class[] = 'text-content'; break;
			case 'row':
				$class[] = 'row no-dropdown'; break;
			case 'col':
				$class[] = 'col need-unwrap'; break;
			case 'tab_container':
				break;
			case 'tab_item':
				$class[] = 'tab-item'; break;
			default:
		}
		return implode(' ',$class);
	}
	
    public function getCategoryTree(){
		if(!$this->_categoryTree){
			$this->_categoryTree = $this->getLayout()->createBlock('Codazon\MegaMenu\Block\Widget\CategoriesTree');
		}
		return $this->_categoryTree;
	}
    
	public function getCacheKeyInfo()
    {
        return [
            'MEGAMENU',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $this->getMenu(),
            $this->getData('use_ajax_menu')
        ];
    }
	public function getIdentities()
    {
        return [\Codazon\MegaMenu\Model\Megamenu::CACHE_TAG . '_' . $this->getMenu()];
    }
}