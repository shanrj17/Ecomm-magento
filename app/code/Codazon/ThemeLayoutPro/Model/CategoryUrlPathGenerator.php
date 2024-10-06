<?php
/**
 *
 * Copyright © 2022 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Codazon\ThemeLayoutPro\Model;

use Magento\Catalog\Model\Category;

class CategoryUrlPathGenerator extends \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator
{
    /**
     * @param Category $category
     *
     * @return bool
     */
    protected function isNeedToGenerateUrlPathForParent($category): bool
    {
        /* Force true when command is run from the CLI */
        if (PHP_SAPI === 'cli') {
            return true;
        }

        return $category->isObjectNew() ||
            $category->getLevel() >= self::MINIMAL_CATEGORY_LEVEL_FOR_PROCESSING;
    }
}