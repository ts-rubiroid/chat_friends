<?php

namespace Itgalaxy\Wc\Exchange1c\Admin;

use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\ClearLogsAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\ClearTempAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\LastRequestResponseAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\LicenseAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\LogsCountAndSizeAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\TempCountAndSizeAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\MetaBoxes\MetaBoxShopOrder;
use Itgalaxy\Wc\Exchange1c\Admin\Other\AdminNoticeIfHasTrashedProductWithGuid;
use Itgalaxy\Wc\Exchange1c\Admin\Other\AdminNoticeIfNotVerified;
use Itgalaxy\Wc\Exchange1c\Admin\Product\DataTabProduct;
use Itgalaxy\Wc\Exchange1c\Admin\ProductAttribute\EditProductAttribute;
use Itgalaxy\Wc\Exchange1c\Admin\ProductAttribute\PageListProductAttribute;
use Itgalaxy\Wc\Exchange1c\Admin\ProductVariation\GuidField;
use Itgalaxy\Wc\Exchange1c\Admin\ProductVariation\HeaderGuidInfo;
use Itgalaxy\Wc\Exchange1c\Admin\RequestProcessing\GetInArchiveLogs;
use Itgalaxy\Wc\Exchange1c\Admin\RequestProcessing\GetInArchiveTemp;
use Itgalaxy\Wc\Exchange1c\Admin\TableColumns\TableColumnProduct;
use Itgalaxy\Wc\Exchange1c\Admin\TableColumns\TableColumnProductAttribute;
use Itgalaxy\Wc\Exchange1c\Admin\TableColumns\TableColumnProductCat;

class AdminLoader
{
    public function __construct()
    {
        $this->bindHooks();
    }

    /**
     * @return void
     */
    private function bindHooks(): void
    {
        if (!is_admin()) {
            return;
        }

        new SettingsPage();
        new PluginActionLinksFilter();
        new PageListProductAttribute();
        new EditProductAttribute();

        // table columns
        new TableColumnProductAttribute();
        new TableColumnProductCat();
        new TableColumnProduct();

        // metaboxes
        new MetaBoxShopOrder();

        // product
        new DataTabProduct();

        // product variation
        new HeaderGuidInfo();
        new GuidField();

        // bind ajax actions
        new ClearLogsAjaxAction();
        new ClearTempAjaxAction();
        new LastRequestResponseAjaxAction();
        new LicenseAjaxAction();
        new LogsCountAndSizeAjaxAction();
        new TempCountAndSizeAjaxAction();

        // bind admin request handlers
        new GetInArchiveLogs();
        new GetInArchiveTemp();

        // bind other admin actions
        new AdminNoticeIfHasTrashedProductWithGuid();
        new AdminNoticeIfNotVerified();
    }
}
