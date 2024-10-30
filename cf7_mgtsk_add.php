<?php

/*
  Plugin Name: Мегатаск [WPCF]
  Depends: Contact From 7
  Description: Плагин для интеграции CMS Wordpress и CRM Мегаплана. Работает как дополнение к основному плагину contactform7. Все формы, созданные через плагин, автоматически интегрируются с вашей CRM.
  Version: 1.0.3
  Author: sadesign
  Author URI: http://sadesign.pro
 * 
 * Выбор имени для отправки  - по названию first-name
 * Выбор поля для электронного адреса по наличию email в имени поля
 * Выбор поля для номера телефона по наличию phone в имени поля
 */


/**
 *  Activation Class
 **/
if (!class_exists('cf7_mgtsk_add_install')) {
    class cf7_mgtsk_add_install
    {
        static function install()
        {
            if (!in_array('contact-form-7/wp-contact-form-7.php', apply_filters('active_plugins', get_option('active_plugins')))) {

                // Deactivate the plugin
                deactivate_plugins(__FILE__);

                // Throw an error in the wordpress admin console
                $error_message = __('Contact From 7 is not installed', 'cf7_mgtsk_add');
                wp_die($error_message);

            }
        }
    }
}

register_activation_hook(__FILE__, array('cf7_mgtsk_add_install', 'install'));

class cf7_mgtsk_add
{

    public function __construct()
    {
        add_action('init', array($this, 'addClasses'));

        add_action('admin_init', array($this, 'addPostTypeSupport'));
        add_action('admin_init', array($this, 'addAjaxActions'));
        add_action('admin_init', array($this, 'addOptions'));

        add_action('admin_head', array($this, 'addStyles'));
        add_action('admin_menu', array($this, 'addSubmenuPage'));

        add_action('init', array($this, 'addActionWpcf7Submit'));
//        add_action('wpcf7_enqueue_scripts', array($this, 'addStylesFileFrontendFunc'));

        add_action('plugins_loaded', array($this, 'addLanguage'));

        require_once __DIR__ . '/cf7_mgtsk_add_filters.php';
        file_put_contents(__DIR__ . "/cf7_mgtsk_add.log", '');
    }

    public static function __autoload($class)
    {
        $prefix = 'cf7_mgtsk_';
        $base_dir = __DIR__ . '/includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $file = $base_dir . $class . ".php";
        if (file_exists($file)) {
            require_once($file);
        }
    }

    function addClasses()
    {
        if (!class_exists('WPCF7_Contact_Form_List_Table')) {
            require_once WPCF7_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
        }

        spl_autoload_register(array('cf7_mgtsk_add', '__autoload'));

        if (!class_exists('WP_Http')) {
            include_once(ABSPATH . WPINC . '/class-http.php');
        }
    }

    function addLanguage()
    {
        load_plugin_textdomain('cf7_mgtsk_add', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function addPostTypeSupport()
    {
        if (post_type_supports('wpcf7_contact_form', 'custom-fields') == false) {
            add_post_type_support('wpcf7_contact_form', 'custom-fields');
        }
    }

    function addSubmenuPage()
    {
        $hook = add_submenu_page(
            'wpcf7',
            __('cf7_mgtsk_add Page Title', 'cf7_mgtsk_add'),
            __('cf7_mgtsk_add Menu Title', 'cf7_mgtsk_add'),
            'wpcf7_read_contact_forms',
            'cf7_mgtsk_add',
            array($this, 'submenuPage')
        );
        add_action('load-' . $hook, array($this, 'addColumns'));
        add_action('load-' . $hook, array($this, 'addStylesFileFunc'));


        $hookLog = add_submenu_page(
            null,
            'cf7_mgtsk_add_log',
            'cf7_mgtsk_add_log',
            'wpcf7_read_contact_forms',
            'cf7_mgtsk_add_log',
            array($this, 'submenuPageLog')
        );
        add_action('load-' . $hookLog, array($this, 'addStylesFileFunc'));
    }

    function submenuPage()
    {
        $list_table = new cf7_mgtsk_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h2>
                <?php _e('cf7_mgtsk_add Page H1', 'cf7_mgtsk_add'); ?>
            </h2>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="postbox-container-1" class="postbox-container">
                        <?php $this->showMgtskKeyField(); ?>
                    </div>
                    <div id="postbox-container-2" class="postbox-container">
                        <?php $list_table->display(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    function submenuPageLog()
    {
        ?>
        <div class="wrap">
            <h2>
                <?php _e('cf7_mgtsk_add Log Page H1', 'cf7_mgtsk_add'); ?>
            </h2>
            <div class="cf7_mgtsk_add_log">
                <?php echo file_get_contents(__DIR__ . '/cf7_mgtsk_add.log'); ?>
            </div>
        </div>
        <?php
    }

    function addOptions()
    {
        // Add the section to reading settings so we can add our
        // fields to it
        add_settings_section(
            'mgtsk_section',
            null,
            '',
            'cf7_mgtsk_add'
        );

        // Add the field with the names and function to use for our new
        // settings, put it in our new section
        add_settings_field(
            'mgtsk_key',
            __('Key MGTSK', 'cf7_mgtsk_add'),
            array($this, 'optionCallback'),
            'cf7_mgtsk_add',
            'mgtsk_section',
            array(
                'type' => 'text',
                'option_name' => 'mgtsk_key',
                'label' => 'Key MGTSK',
                'label_for' => 'mgtsk_key_text',
            )
        );
        add_settings_field(
            'mgtsk_log',
            __('Write log', 'cf7_mgtsk_add'),
            array($this, 'optionCallback'),
            'cf7_mgtsk_add',
            'mgtsk_section',
            array(
                'type' => 'checkbox',
                'option_name' => 'mgtsk_log',
                'label' => 'Write log?',
                'label_for' => 'mgtsk_log_checkbox',
                'description' => sprintf(__('go to <a href="%s">log page</a>', 'cf7_mgtsk_add'), '/wp-admin/admin.php?page=cf7_mgtsk_add_log')
            )
        );

        // Register our setting so that $_POST handling is done for us and
        // our callback function just has to echo the <input>
        register_setting('cf7_mgtsk_add', 'mgtsk_section');
    }

    function showMgtskKeyField()
    {
        ?>
        <div class="postbox">
            <h3>
                <?php _e('Settings Box title', 'cf7_mgtsk_add'); ?>
            </h3>
            <div class="inside">
                <?php _e('cf7_mgtsk_add settings', 'cf7_mgtsk_add'); ?>
                <form method="POST" action="options.php">
                    <?php
                    settings_fields('cf7_mgtsk_add');
                    do_settings_sections('cf7_mgtsk_add');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    function addColumns()
    {
        $current_screen = get_current_screen();
        add_filter('manage_' . $current_screen->id . '_columns', array('cf7_mgtsk_List_Table', 'define_columns'));
    }

    function addStylesFileFunc()
    {
        add_action('admin_enqueue_scripts', array($this, 'addStylesFile'));
    }

    function addStylesFile()
    {
        wp_enqueue_style('cf7_mgtsk', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_script('cf7_mgtsk', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'));
    }

    function addStylesFileFrontendFunc()
    {
        wp_enqueue_script('cf7_mgtsk', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', array('jquery'));
    }

    function addStyles()
    {
        ?>
        <style>
            .technical-pre {
                width: 500px;
                margin: 10px 0 10px 180px;
                padding: 20px;
                background: #ccc;
                border-radius: 5px;
            }
        </style>
        <?php
    }

    function addAjaxActions()
    {
        /* TODO disabled на время ajax событий */
        add_action('wp_ajax_setWpcf7Custom', array($this, 'setWpcf7Custom'));
    }

    function setWpcf7Custom()
    {
        $post_id = $_POST['post_id'];
        $field = $_POST['field'];
        $value = $_POST['value'];

        if ($field == 'use_mgtsk') {
            $form = WPCF7_ContactForm::find(array('p' => $post_id));
            $cRaw = $form[0]->prop('form');
            $c = cf7_mgtsk_Helper::cleanFormContent($cRaw);

            if ($value == 'true') {
                update_post_meta($post_id, $field, true);
                $use_mgtsk = true;
            } else {
                delete_post_meta($post_id, $field);
                $use_mgtsk = false;
            }
            $result['fields'] = cf7_mgtsk_Helper::showTable($c, $use_mgtsk);
        }
        $val = get_post_meta($post_id, $field, 1);
        $result['value'] = $val;
        wp_send_json($result);
        wp_die();
    }

    function addActionWpcf7Submit()
    {
        add_action('wpcf7_submit', array($this, 'wpcf7Submit'), 10, 2);
    }

    function wpcf7Submit($object, $result)
    {
        $use_mgtsk = get_post_meta($object->id(), 'use_mgtsk', true);
        if (!empty($use_mgtsk) && $result['status'] == 'mail_sent') {
            $fields = cf7_mgtsk_Helper::cleanFormContent($object->prop('form'));
            $valueCommentRaw[] = array(
                'name' => __('form_title', 'cf7_mgtsk_add'),
                'value' => $object->title()
            );
            $valueEmailRaw = array();
            $valuePhoneRaw = array();
            $valueFirstNameRaw = array();
            foreach ($fields as $i => $row) {
                $rowEmail = array();
                $rowPhone = array();
                $rowFirstName = array();
                if (!empty($_POST[$row['name']])) {
                    if (is_array($_POST[$row['name']])) {
                        $value = implode(', ', $_POST[$row['name']]);
                    } else {
                        $value = $_POST[$row['name']];
                    }
                    switch ($row['name']) {
                        case (preg_match('/(email)/', $row['name'], $rowEmail) ? true : false) :
                            $valueEmailRaw[] = $value;
                            break;
                        case (preg_match('/(phone)/', $row['name'], $rowPhone) ? true : false) :
                            $valuePhoneRaw[] = $value;
                            break;
                        case (preg_match('/(first-name)/', $row['name'], $rowFirstName) ? true : false) :
                            $valueFirstNameRaw[] = $value;
                            break;
                        default:
                            $name = $row['display_name'];
                            if (empty($row['display_name'])) {
                                $name = $row['name'];
                            }
                            $valueCommentRaw[] = array(
                                'name' => $name,
                                'value' => $value
                            );
                    }
                }
            }
            /* TODO Добавить проверку на наличие email && mgtsk_key */
            $mgtskOption = get_option('mgtsk_section');
            $values['mgtsk_key'] = $mgtskOption['mgtsk_key'];

            $valueEmailRaw = array_unique($valueEmailRaw);
            if (!empty($valueEmailRaw)) {
                $values['email'] = array_shift($valueEmailRaw);
                if (!empty($valueEmailRaw)) {
                    $valueCommentRaw[] = array('name' => __('other_email', 'cf7_mgtsk_add'), 'value' => implode(', ', $valueEmailRaw));
                }
            }

            $valuePhoneRaw = array_unique($valuePhoneRaw);
            if (!empty($valuePhoneRaw)) {
                $values['phone'] = array_shift($valuePhoneRaw);
                if (!empty($valuePhoneRaw)) {
                    $valueCommentRaw[] = array('name' => __('other_phone', 'cf7_mgtsk_add'), 'value' => implode(', ', $valuePhoneRaw));
                }
            }

            if (!empty($valueFirstNameRaw)) {
                $values['first-name'] = implode(', ', $valueFirstNameRaw);
            }

            if (!empty($valueCommentRaw)) {
                $values['comment'] = cf7_mgtsk_Helper::streamComment($valueCommentRaw);
            }

            $url = apply_filters('cf7_mgtsk_acceptor_filter', null);
            $body = apply_filters('cf7_mgtsk_values_filter', $values, $_POST);
            if (!empty($values['mgtsk_key'])) {
                $request = new WP_Http;
                $values['mgtsk_key'] = $mgtskOption['mgtsk_key'];
                if (!empty($mgtskOption['mgtsk_log'])) {
                    file_put_contents(
                        __DIR__ . "/cf7_mgtsk_add.log",
                        date('l jS \of F Y h:i:s A') . ': ' . $url . ': ' . serialize(array('method' => 'POST', 'body' => $body)) . PHP_EOL . PHP_EOL,
                        FILE_APPEND
                    );
                }
                $result = $request->request($url, array('method' => 'POST', 'body' => $body));
            }
        }
    }

    function optionCallback($val)
    {
        $mgtsk_section = get_option('mgtsk_section');
        ?>
        <input
                type="<?php echo $val['type']; ?>"
                name="mgtsk_section[<?php echo $val['option_name']; ?>]"
                id="<?php echo $val['option_name']; ?>_<?php echo $val['type']; ?>"
            <?php
            switch ($val['type']) {
                case 'checkbox':
                    if ($mgtsk_section[$val['option_name']] == 'on') {
                        echo 'checked="checked"';
                    }
                    break;
                default:
                    echo 'value="' . $mgtsk_section[$val['option_name']] . '"';
            }
            ?>
        />
        <?php
        if (!empty($val['description'])) {
            echo '<p>' . $val['description'] . '</p>';
        }
    }
}

$cf7_mgtsk_add = new cf7_mgtsk_add();