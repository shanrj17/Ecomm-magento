<?php
/**
 * Copyright © 2016 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Blog\Model\Config\Source;

class Show implements \Magento\Framework\Option\ArrayInterface
{
    
    public function toOptionArray()
    {
        return [
        	['value' => 'featured_img', 'label' => __('Featured Image')],
        	['value' => 'title', 'label' => __('Title')],
        	['value' => 'short_content', 'label' => __('Short Content')],
        	['value' => 'publish_time', 'label' => __('Published Date')],
			['value' => 'author', 'label' => __('Author')],
            ['value' => 'category', 'label' => __('Category')]
        ];
    }

    public function toArray()
    {
        return $this->toOptionArray();
    }
    
    
}