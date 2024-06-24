<?php

namespace SheaDawson\Blocks\Controllers;

use SheaDawson\Blocks\Model\Block;
use SheaDawson\Blocks\Model\BlockSet;
use SheaDawson\Blocks\Forms\GridFieldConfigBlockManager;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * BlockAdmin.
 *
 * @author Shea Dawson <shea@silverstripe.com.au>
 */
class BlockAdmin extends ModelAdmin
{
    private static $managed_models = [
        Block::class,
        BlockSet::class,
    ];

    private static $url_segment = "block-admin";

    private static $menu_title = "Blocks";

    private static $menu_icon = 'sheadawson/silverstripe-blocks: images/blocks.png';

    public $showImportForm = false;

    private static $dependencies = [
        "blockManager" => '%$blockManager',
    ];

    public $blockManager;

    /**
     * @return array
     **/
    public function getManagedModels()
    {
        $models = parent::getManagedModels();

        // remove blocksets if not in use (set in config):
        if (!$this->blockManager->getUseBlockSets()) {
            unset($models['BlockSet']);
        }

        return $models;
    }

    /**
     * @return Form
     **/
    public function getEditForm($id = null, $fields = null)
    {
        Versioned::set_stage('Stage');
        $form = parent::getEditForm($id, $fields);

        if ($blockGridField = $form->Fields()->fieldByName('Block')) {
            $blockGridField->setConfig(GridFieldConfigBlockManager::create(true, true, false));
            $config = $blockGridField->getConfig();
            $dcols = $config->getComponentByType(GridFieldDataColumns::class);
            $dfields = $dcols->getDisplayFields($blockGridField);
            unset($dfields['BlockArea']);
            $dcols->setDisplayFields($dfields);
        }

        return $form;
    }

    public function getSearchContext()
    {
        $context = parent::getSearchContext();
        $fields = $context->getFields();
        $subclasses = $this->blockManager->getBlockClasses();
        if ($fields->dataFieldByName('q[ClassName]') && sizeof($subclasses) > 1) {
            $fields->dataFieldByName('q[ClassName]')->setSource($subclasses);
            $fields->dataFieldByName('q[ClassName]')->setEmptyString('(any)');
        } else {
            $fields->removeByName('q[ClassName]');
        }
        return $context;
    }
}
