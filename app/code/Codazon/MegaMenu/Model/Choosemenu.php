<?php
/**
 * Copyright © 2021 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\MegaMenu\Model;
class Choosemenu implements \Magento\Framework\Option\ArrayInterface
{
	protected $_collection;
	public function __construct(
		\Codazon\MegaMenu\Model\ResourceModel\Megamenu\Collection $collection
	){
		$this->_collection = $collection;
	}
	public function toOptionArray()
    {
		
        $collection = $this->_collection;
		$menu = [
            ['value' => '', 'label' => __('---')]
        ];
		foreach($collection as $item) {
            $menu[] = [
                'label' => $item->getTitle(),
                'value' => $item->getIdentifier() ,
            ];
        }	
		return $menu;
    }
}
