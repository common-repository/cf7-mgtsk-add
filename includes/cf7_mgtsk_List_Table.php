<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if (!class_exists('WPCF7_Contact_Form_List_Table')) {
    require_once WPCF7_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
}

class cf7_mgtsk_List_Table extends WPCF7_Contact_Form_List_Table {

    static function define_columns($columns) {
        return array(
//            'cb' => '<input type="checkbox" />',
            'title' => __('Title', 'contact-form-7'),
            'use_mgtsk' => __('use mgtsk?', 'cf7_mgtsk_add'),
            'fields' => __('fields in form', 'cf7_mgtsk_add'),
        );;
    }

    function get_bulk_actions() {
        return array();
    }

    function column_use_mgtsk($item) {
        $meta = get_post_meta($item->id(), 'use_mgtsk', 1);
        if (!empty($meta)) {
            $checked = 'checked="checked"';
            $value = 1;
        } else {
            $checked = '';
            $value = 0;
        }
        return "<input type='checkbox' name='use_mgtsk' data-id='{$item->id()}' value='{$value}' {$checked} />";
    }

    function column_fields($item) {
        $meta = get_post_meta($item->id(), 'use_mgtsk', 1);
        $cRaw = $item->prop('form');
        $c = cf7_mgtsk_Helper::cleanFormContent($cRaw);
        return cf7_mgtsk_Helper::showTable($c, $meta);
    }

}
