<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

class SectionForPrices
{
    public static function render()
    {
        Section::header(esc_html__('For prices', 'itgalaxy-woocommerce-1c'));

        FieldSelect::render(
            [
                'title' => esc_html__('Price processing type', 'itgalaxy-woocommerce-1c'),
                'options' => apply_filters(
                    'itglx_wc1c_price_work_rules',
                    [
                        'regular' => esc_html__(
                            'Mode - 1: Only the base price is set (Price Type 1)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'regular_and_sale' => esc_html__(
                            'Mode - 2: The base and sale price are set (Price Type 1 - basic, Price '
                            . 'Type 2 - sale)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'regular_and_show_list' => esc_html__(
                            'Mode - 3: Only the base price is set (Price Type 1) and show the price '
                            . 'list in the product page',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'regular_and_show_list_and_apply_price_depend_cart_totals' => esc_html__(
                            'Mode - 4: Only the base price is set (Price Type 1) and show the price '
                            . 'list in the product page and apply price types depending on the '
                            . 'amount in the cart',
                            'itgalaxy-woocommerce-1c'
                        ),
                    ]
                ),
                'description' => esc_html__(
                    'Choose the rule of working with prices that is convenient for you.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
            'price_work_rule'
        );

        $priceTypes = [];
        $allPricesTypes = get_option('all_prices_types', []);

        if (is_array($allPricesTypes)) {
            foreach ($allPricesTypes as $key => $name) {
                $priceTypes[$key] = is_array($name) ? $name['name'] : $name;
            }
        } ?>
        <hr>
        <?php
        FieldCheckbox::render(
            [
                'title' => esc_html__(
                    'Delete sale price (works for - Mode 1)',
                    'itgalaxy-woocommerce-1c'
                ),
                'description' => '',
            ],
            'remove_sale_price'
        ); ?>
        <hr>
        <?php
        if (!empty($priceTypes)) {
            echo '<table class="itglx_fb_table itglx_fb_table-sm itglx_fb_mb-1"><tr>';
            echo '<td>';
            FieldSelect::render(
                [
                    'title' => esc_html__('Price Type 1', 'itgalaxy-woocommerce-1c'),
                    'options' => $priceTypes,
                ],
                'price_type_1'
            );

            echo '</td>';
            echo '<td>';

            FieldInput::render(
                [
                    'title' => esc_html__('Caption:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'text',
                    'description' => esc_html__(
                        'Used for mode 3.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'price_type_1_text'
            );

            echo '</td>';
            echo '<td>&nbsp;</td></tr>';

            if (count($priceTypes) > 1) {
                $priceTypesOptions = ['' => esc_html__('Not chosen', 'itgalaxy-woocommerce-1c')] + $priceTypes;

                for ($i = 2; $i <= count($priceTypes); ++$i) {
                    echo '<tr>';
                    echo '<td>';

                    FieldSelect::render(
                        [
                            'title' => esc_html__('Price Type', 'itgalaxy-woocommerce-1c') . ' ' . $i,
                            'options' => $priceTypesOptions,
                        ],
                        'price_type_' . $i
                    );

                    echo '</td>';
                    echo '<td>';

                    FieldInput::render(
                        [
                            'title' => esc_html__('Caption:', 'itgalaxy-woocommerce-1c'),
                            'type' => 'text',
                            'description' => esc_html__(
                                'Used for mode 3.',
                                'itgalaxy-woocommerce-1c'
                            ),
                        ],
                        'price_type_' . $i . '_text'
                    );

                    echo '</td>';
                    echo '<td>';

                    FieldInput::render(
                        [
                            'title' => esc_html__('Cart totals:', 'itgalaxy-woocommerce-1c'),
                            'type' => 'number',
                            'description' => esc_html__(
                                'Used for mode 4. Use price type if there is more in the cart.',
                                'itgalaxy-woocommerce-1c'
                            ),
                        ],
                        'price_type_' . $i . '_summ'
                    );

                    echo '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            } else {
                echo '</table>';
                ?>
                <div class="itglx_fb_form-group">
                    <label class="itglx_fb_form-group-label">
                        <?php esc_html_e('Price Type 2', 'itgalaxy-woocommerce-1c'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('The selection will appear if there are more than 1 price type in the unloading.', 'itgalaxy-woocommerce-1c'); ?>
                    </p>
                </div>
                <hr>
                <?php
            }
        } else {
            ?>
            <div class="itglx_fb_form-group">
                <label class="itglx_fb_form-group-label">
                    <?php esc_html_e('Price Type 1', 'itgalaxy-woocommerce-1c'); ?>
                </label>
                <input type="text"
                    class="itglx_fb_form-group-input itglx_fb_form-input-input"
                    name="empty_price_type_key"
                    placeholder="<?php esc_attr_e('Price Type Code', 'itgalaxy-woocommerce-1c'); ?>">
                <input type="text"
                    class="itglx_fb_form-group-input itglx_fb_form-input-input"
                    name="empty_price_type_name"
                    placeholder="<?php esc_attr_e('Price Type Name', 'itgalaxy-woocommerce-1c'); ?>">
            </div>
            <hr>
            <?php
        }

        FieldCheckbox::render(
            [
                'title' => esc_html__('Apply change control', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, then reprocessing and recording of data on prices occurs when there are changes in '
                    . 'the data on offer prices in the CML (by default, the presence of changes is not control), '
                    . 'which allows you to further reduce the load in the process of working with data. '
                    . 'Should not be enabled if you have not yet finished manipulating the selection of the mode and '
                    . 'price types.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
            'prices_change_control'
        );

        Section::footer();
    }
}
