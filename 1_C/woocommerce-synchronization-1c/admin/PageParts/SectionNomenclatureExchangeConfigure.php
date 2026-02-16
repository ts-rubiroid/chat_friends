<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureForAttributesTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureForCategoriesTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureForImagesTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureForOffersTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureForProductsTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureMainTab;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs\SectionNomenclatureSkipExcludeTab;

class SectionNomenclatureExchangeConfigure
{
    public static function render()
    {
        $section = [
            'title' => esc_html__('Product catalog (nomenclature)', 'itgalaxy-woocommerce-1c'),
            'tabs' => [
                SectionNomenclatureMainTab::getSettings(),
                SectionNomenclatureForProductsTab::getSettings(),
                SectionNomenclatureForAttributesTab::getSettings(),
                SectionNomenclatureForImagesTab::getSettings(),
                SectionNomenclatureForCategoriesTab::getSettings(),
                SectionNomenclatureForOffersTab::getSettings(),
                SectionNomenclatureSkipExcludeTab::getSettings(),
            ],
        ];

        Section::render(
            apply_filters('itglx_wc1c_admin_section_nomenclature_fields', $section)
        );
    }
}
