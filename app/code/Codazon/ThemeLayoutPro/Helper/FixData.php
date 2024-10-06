<?php
/**
* Copyright © 2020 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\ThemeLayoutPro\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Codazon\ThemeLayoutPro\Model\CodazonTheme;

class FixData extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $helper;
    
    protected $importHelper;
    
    protected $index;
    
    protected $storeOptions = [];
    
    protected $identifier;
    
    protected $setup;
    
    protected $themeFactory;
    
    const PATCH_FILE = 'fix_data.xml';
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Codazon\ThemeLayoutPro\Helper\Data $helper,
        \Codazon\Core\Helper\Import $importHelper,
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Theme\Model\ThemeFactory $themeFactory
    ) {
        parent::__construct($context);
        $this->importHelper = $importHelper;
        $this->setup = $setup;
        $this->themeFactory = $themeFactory;
    }
    
    public function fixTemplate()
    {
        $objectManager = $this->importHelper->getObjectManager();
        $collection = $objectManager->get(\Codazon\ThemeLayoutPro\Model\ResourceModel\Template\Collection::class);
        $connection = $collection->getConnection();
        $connection->dropTable($collection->getTable('themelayout_template'));
        $connection->dropTable($collection->getTable('themelayout_template_set'));
        $this->createTemplateTables();
        $importModel = $objectManager->get(\Codazon\ThemeLayoutPro\Model\Import::class);
        $importModel->importTemplateSet();
        $importModel->importTemplate();
        return $this;
    }
    
    protected function createTemplateTables()
    {
        $setup = $this->setup;
        $connection = $setup->getConnection();
        /* Template Type Table */
        $table = $connection->newTable(
            $setup->getTable('themelayout_template_set')
        )->addColumn(
            'template_set_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['primary' => true, 'identity' => true, 'unsigned' => true, 'nullable' => false],
            'Set id'
        )->addColumn(
            'template_set_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Set name'
        )->addColumn(
            'template_set_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Preview image'
        )->setComment(
            'Template set'
        );
        $connection->createTable($table);
        
        /* Template Table */
        $table = $connection->newTable(
            $setup->getTable('themelayout_template')
        )->addColumn(
            'template_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['primary' => true, 'identity' => true, 'unsigned' => true, 'nullable' => false],
            'Template id'
        )->addColumn(
            'template_set_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true, 'unsigned' => true],
            'Template type'
        )->addColumn(
            'template_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Template Name'
        )->addColumn(
            'template_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Preview image'
        )->addColumn(
            'content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Template content'
        )->addForeignKey(
            $setup->getFkName(
                'themelayout_template', 'template_set_id', 'themelayout_template_set', 'template_set_id'
            ),
            'template_set_id',
            $setup->getTable('themelayout_template_set'),
            'template_set_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Template'
        );
        $connection->createTable($table);
    }
    
    public function fixProductStyle()
    {
        $objectManager = $this->importHelper->getObjectManager();
        $mainCollection = $objectManager
            ->get(\Codazon\ThemeLayoutPro\Model\ResourceModel\MainContent\CollectionFactory::class)
            ->create()
            ->setStoreId(0)->addAttributeToSelect(['themelayout_content', 'store_options']);
        foreach ($mainCollection as $item) {
            $item->setStoreId(0);
            $customField = $item->getData('custom_fields');
            if ($customField) {
                $customField = json_decode($customField, true);
                if (empty($customField['category_view_less'])) {
                    $customField['category_view_less'] = 'product-01.less.css';
                }
                $less = $customField['category_view_less'];
                $fileNum = explode('.less.css', $less);
                $fileNum = explode('product-', $fileNum[0]);
                $fileNum = $fileNum[1];
                if ($fileNum) {
                    $customField['category_view_style'] = "product/list/list-styles/list-style{$fileNum}.phtml";
                }
                if (empty($customField['product_view_less'])) {
                    $customField['product_view_less'] = '_product-view-style-01.less.css';
                }
                $less = $customField['product_view_less'];
                $fileNum = explode('.less.css', $less);
                $fileNum = explode('_product-view-style-', $fileNum[0]);
                $fileNum = $fileNum[1];
                if ($fileNum) {
                    $customField['product_view_style'] = "catalog_product_view_style{$fileNum}";
                }
                if (isset($customField['product_list_less'])) {
                    unset($customField['product_list_less']);
                }
                $customField = json_encode($customField);
                $item->setData('custom_fields', $customField);
            }
            
            $content = $item->getData('themelayout_content');
            $identifier = $item->getData('identifier');
            $result = $this->filerContent($content, $identifier);
            $item->setData('themelayout_content', $result['content']);
            $item->setData('store_options', json_encode($result['store_options']));
            $item->save();
        }
        return $this;
    }
    
    public function filerContent($content, $identifier)
    {
        $this->index = 0;
        $this->identifier = $identifier;
        $this->storeOptions = [];
        $content = preg_replace_callback(
            '/{{widget+[^{{]+(:?ProductFilter|BlogPostList)+[^}}]+}}/i',
            function($matches) {
                $this->index++;
                if (substr_count($matches[0], 'main_opt_id') > 1) {
                    $matches[0] = preg_replace('/main_opt_id=+([^"]+)"+([^"])+"/i', '', $matches[0]);
                }
                if (strpos($matches[0], 'main_opt_id') !== false) {
                    $replace = preg_replace_callback('/main_opt_id=+([^"]+)"+([^"])+"/i', function($m) {
                        $param = $m[0];
                        $sign = $m[1];
                        $param = 'main_opt_id='.$sign.'"'.$this->identifier.'||widget_'.$this->index.$sign.'"';
                        return $param;
                    }, $matches[0]);
                } else {
                    $replace = preg_replace_callback('/ type=+([^"]+)"+([^"])+"/i', function($m) {
                        $param = $m[0];
                        $sign = $m[1];
                        $param = $param.' main_opt_id='.$sign.'"'.$this->identifier.'||widget_'.$this->index.$sign.'"';
                        return $param;
                    }, $matches[0]);
                }
                $widget = $replace;
                if (strpos($matches[0], 'ajax_load') !== false) { 
                    $widget = preg_replace_callback('/ajax_load=+([^"]+)"+([^"])+"/i', function($m) {
                        return str_replace("1", "0", $m[0]);
                    }, $widget);
                } else {
                    $widget = preg_replace_callback('/main_opt_id=+([^"]+)"+([^"])+"/i', function($m) {
                        $param = $m[0];
                        $sign = $m[1];
                        $param = $param.' ajax_load='.$sign.'"0'.$sign.'"';
                        return $param;
                    }, $widget);
                }
                $widget = str_replace(['\"', '\\\\'],['"', '\\'], json_decode('{"widget_'.$this->index.'":"'.$widget.'"}', true));
                $this->storeOptions["widget_" . $this->index] = $widget["widget_".$this->index];
                return $replace;
            },
            (string)$content
        );
        return [
            'content'       => $content,
            'store_options' => $this->storeOptions
        ];
    }
    
    public function fixData()
    {
        $this->fixProductStyle();
        $patchFile = $this->importHelper->getEtcXmlFilePath(self::PATCH_FILE, 'Codazon_ThemeLayoutPro');
        $patchList = $this->importHelper->getArrayFromXmlFile($patchFile);
        $objectManager = $this->importHelper->getObjectManager();
        
        $patchList = $patchList['patch_list']['item'];

        if (isset($patchList['process'])) {
            $patchList = [$patchList];
        }

        foreach ($patchList as $listItem) {
            $colClass = $listItem['collection'];
            $modelClass = $listItem['model'];
            $fieldToSelect = $listItem['field_to_select'];
            $attributeToSelect = $listItem['attribute_to_select'];
            $processes = $listItem['process']['item'];
            
            
            if (isset($processes['condition']) || isset($processes['patch'])) {
                $processes = [$processes];
            }
            
            foreach ($processes as $process) {
                $collection = $objectManager->create($colClass);
                $condition = isset($process['condition']) ? $process['condition'] : [];
                $patches = $process['patch']['item'];
                if (isset($patches['field'])) {
                    $patches = [$patches];
                }
                
                if ($fieldToSelect) {
                    $collection->addFieldToSelect(explode(',', $fieldToSelect));
                }
                if ($attributeToSelect) {
                    $collection->addAttributeToSelect(explode(',', $attributeToSelect));
                }
                if (!empty($condition['field'])) {
                    $collection->addFieldToFilter($condition['field'], $condition['value']);
                }
                if (!empty($condition['attribute'])) {
                    $collection->addAttributeToFilter($condition['attribute'], $condition['value']);
                }
                foreach ($collection as $itemModel) {
                    foreach ($patches as $patch) {
                        $model = $objectManager->create($modelClass)->setStore(0)->load($itemModel->getId());
                        $fieldValue = $model->getData($patch['field']);
                        if (strpos((string)$fieldValue, $patch['search']) === false) {
                            if (isset($patch['not_exist'])) {
                                $fieldValue = str_replace($patch['not_exist']['search'], $patch['not_exist']['replace'], $fieldValue);
                                $model->setStoreId(0)->load($model->getId())->setData($patch['field'], $fieldValue)->save();
                            }
                        } else {
                            if (isset($patch['exist'])) {
                                $fieldValue = str_replace($patch['exist']['search'], $patch['exist']['replace'], $fieldValue);
                                $model->setStoreId(0)->load($model->getId())->setData($patch['field'], $fieldValue)->save();
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    public function fixFiles()
    {
        
        try {
            $removeFile = $this->importHelper->getModuleReader()->getModuleDir('view', 'Codazon_ShoppingCartPro') . '/frontend/layout';
            if ($this->importHelper->getIo()->fileExists($removeFile, false)) {
                $this->importHelper->getIo()->rmdirRecursive($removeFile);
            }
            $removeFile = $this->importHelper->getModuleReader()->getModuleDir('view', 'Codazon_QuickShop') . '/frontend/layout/default.xml';
            if ($this->importHelper->getIo()->fileExists($removeFile)) {
                $this->importHelper->getIo()->rmdirRecursive($removeFile);
            }
        } catch (\Execption $e) {
            
        }
        return $this;
    }
 
    public function createDesignTables($setup = null)
    {
        if ($setup === null) {
            $setup = $this->setup;
        }
        $connection = $setup->getConnection();
        $table = $connection->newTable($setup->getTable('themelayout_design'))->addColumn(
            'theme_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
            "Entity ID"
        )->addColumn(
            'identifier',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            "Identifier"
        )->addColumn(
            'title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            "Title"
        )->addColumn(
            'design_group',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            "Design Group"
        )->addColumn(
            'preview',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            "Preview"
        )->setComment('Codazon Design Table');
        $connection->createTable($table);
    }
    
    public function importDesigns()
    {
        $defaultThemeCode = CodazonTheme::DEFAULT_THEME_CODE;
        $defautTheme = $this->themeFactory->create()->load($defaultThemeCode, 'code');
        $themeList = $this->themeFactory->create()->getCollection()
                ->addFieldToFilter('area', 'frontend')
                ->addFieldToFilter(['parent_id', 'code'], [$defautTheme->getId(), $defaultThemeCode])
                ->getItems();
        $objectManager = $this->importHelper->getObjectManager();
        $factory = $objectManager->get(\Codazon\ThemeLayoutPro\Model\DesignFactory::class);
        $designGroup = str_replace(['Codazon/', '_default'], ['', ''], $defaultThemeCode);
        
        $designPath = \Codazon\ThemeLayoutPro\Model\Design::PARENT_DIR;
        $styleHelper = $this->importHelper->getStylesHelper();
        $designDir = $styleHelper->getMediaDir($designPath);
        $io = $this->importHelper->getIo();
        $io->checkAndCreateFolder($designDir);        
        $themePreviewDir = $styleHelper->getMediaDir('theme/preview');
        $directoryList = $objectManager->get(\Magento\Framework\Filesystem\DirectoryList::class);
        $appDir = $directoryList->getPath('app');
        
        $pathSearch = str_replace('default', '', $defaultThemeCode);
        $pathReplace = str_replace('_default', '/', $defaultThemeCode);
        
        $connection = $this->setup->getConnection();
        $logoDir = $styleHelper->getMediaDir('codazon/logo');
        $io->checkAndCreateFolder($logoDir);
        
        $previewDir = $styleHelper->getMediaDir(\Codazon\ThemeLayoutPro\Model\Design::PREVIEW_IMAGE_PATH);
        $io->checkAndCreateFolder($previewDir);
        
        $tableConfig = $this->setup->getTable('themelayout_config_data');
        $tableDesign = $this->setup->getTable('themelayout_design');
        $designRows = [];
        $configRows = [];
        $themeIds = [];
        foreach ($themeList as $item) {
            $id = $item->getId();
            $themeIds[] = $id;
            if ($factory->create()->load($id)->getId()) {
                continue;
            }
            if ($item->getData('code') === 'Codazon/' . CodazonTheme::DEFAULT_NAMESPACE . '_child') {
                continue;
            }
            $themePath = str_replace($pathSearch, $pathReplace, $item->getFullPath());
            $themePath = $appDir.'/design/'.$themePath;
            
            $identifier = str_replace(['Codazon/', $designGroup . '_'], ['', ''], $item->getCode());
            
            $orgLogo = $themePath . '/web/images/logo.svg';
            $logoPath = "{$designGroup}/{$identifier}/logo.svg";
            $logo = "{$logoDir}/{$logoPath}";
            
            
            if ($this->importHelper->fileExists($orgLogo)) {
                $io->checkAndCreateFolder($logoDir . "/{$designGroup}/{$identifier}");
                $io->cp($orgLogo, $logo);
                $configRows[] = [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => 'themelayoutpro/header/logo',
                    'theme_id' => $id,
                    'value' => $logoPath
                ];
            }

            $iDesignDir = "{$designDir}/{$designGroup}/{$identifier}";
            $io->checkAndCreateFolder($iDesignDir);
            
            $orgXmlConfig = $themePath . '/etc/theme_config.xml';

            if ($this->importHelper->fileExists($orgXmlConfig)) {
                $xmlConfig = $iDesignDir . '/theme_config.xml';
                $io->cp($orgXmlConfig, $xmlConfig);
            }
            
            $orgPreview = $themePreviewDir . '/'. $item->getData('preview_image');
            $previewPath = "{$designGroup}/{$identifier}/preview.jpg";
            $preview = "{$previewDir}/{$previewPath}";
            $io->checkAndCreateFolder($previewDir . "/{$designGroup}/{$identifier}");
            $io->cp($orgPreview, $preview);
            $designRows[] = [
                'theme_id' => $id,
                'title' => $item->getData('theme_title'),
                'identifier' => $identifier,
                'design_group' => $designGroup,
                'preview' => $previewPath
            ];
        }
        if (count($configRows)) {
            foreach ($configRows as $row) {
                $where = [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => 'themelayoutpro/header/logo',
                    'theme_id' => $row['theme_id']
                ];
                $whereString = '';
                foreach ($where as $key => $value) {
                    $where[$key] = "{$key} = '{$value}'";
                }
                $where = implode(" AND ", $where);
                $select = $connection->select()->from($tableConfig, ['value'])->where($where);
                $result = $connection->fetchCol($select);
                if (count($result) == 0) {
                    $connection->insert($tableConfig, $row);
                } else {
                    $connection->update($tableConfig, $row, $where);
                }
            }
        }
        if (count($designRows)) {
            foreach ($designRows as $row) {
                try {
                    $connection->beginTransaction();
                    $connection->insert($tableDesign, $row);
                    $connection->commit();
                } catch(\Exception $e) {
                    $connection->rollBack();
                }
            }
        }
        
        $xmlPathThemeId = \Codazon\ThemeLayoutPro\Model\Design::XML_PATH_THEME_ID;
        if ($themeIds) {
            $themeIdsStr = implode(",", $themeIds);
            $tableCoreConfig = $this->setup->getTable('core_config_data');
            $select = $connection->select()->from($tableCoreConfig)
                ->where("path = 'design/theme/theme_id' AND value in ({$themeIdsStr})");
            $result = $connection->fetchAll($select);
            
            $select = $connection->select()->from($tableCoreConfig)
                ->where("path = '{$xmlPathThemeId}'");
            $result2 = $connection->fetchAll($select);
            if (count($result2) === 0) {
                foreach ($result as $row) {
                    $data = $row;
                    unset($data['config_id']);
                    unset($data['updated_at']);
                    $data['path'] = $xmlPathThemeId;
                    $connection->insert($tableCoreConfig, $data);
                }
            }
        }
        return $this;
    }
 
    public function fixDesign()
    {
        $this->createDesignTables();
        $this->importDesigns();
    }
    
    public function isUsingTheme($themeId)
    {
        $connection = $this->setup->getConnection();
        $tableCoreConfig = $this->setup->getTable('core_config_data');
        $select = $connection->select()->from($tableCoreConfig)
            ->where("path = 'design/theme/theme_id' AND value = {$themeId}");
        $result = $connection->fetchAll($select);
        if (count($result)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getChildrenThemes($themeId)
    {
        $themeList = $this->themeFactory->create()->getCollection()
                ->addFieldToFilter('area', 'frontend')
                ->addFieldToFilter(['parent_id'], [$themeId]);
        return $themeList;
    }
    
    public function removeTheme($themeId)
    {
        if (!$themeId) {
            throw new \Exception(__("Theme id is empty"));
        }
        if ($this->isUsingTheme($themeId)) {
            throw new \Exception(__('This theme is using. You must activate another theme before removing it.'));
        }
        $children = $this->getChildrenThemes($themeId);
        if ($children->count()) {
            throw new \Exception(__('This theme is parent of other themes. Please remove all themes with following IDs before removing it: %1.', implode(", ", $children->getAllIds())));
        }
        $objectManager = $this->importHelper->getObjectManager();
        $io = $this->importHelper->getIo();
        $namespace = CodazonTheme::DEFAULT_NAMESPACE;
        $theme = $this->themeFactory->create()->load($themeId);
        if (!$theme->getId()) {
            throw new \Exception(__('There is no theme with this ID (%1).', $themeId));
        }
        if (in_array($theme->getCode(), [CodazonTheme::DEFAULT_THEME_CODE, 'Magento/blank', 'Magento/luma'])) {
            throw new \Exception(__('You cannot remove default theme with this tool.'));
        }
        if ($theme->getData('area') === 'adminhtml') {
            throw new \Exception(__('You cannot remove backend theme with this tool.'));
        }
        
        $directoryList = $objectManager->get(\Magento\Framework\Filesystem\DirectoryList::class);
        $appDir = $directoryList->getPath('app');
        $themePath = $appDir .'/design/'. str_replace('Codazon/'.$namespace.'_', 'Codazon/'.$namespace.'/', $theme->getFullPath());
        $themeData = $theme->getData();
        $theme->delete();
        $io->rmdirRecursive($themePath);
        return $themeData;
    }
}