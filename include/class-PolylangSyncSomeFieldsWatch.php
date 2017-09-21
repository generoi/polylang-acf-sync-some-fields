<?php


if (!class_exists('PolylangSyncSomeFieldsWatch')) :
    class PolylangSyncSomeFieldsWatch
    {
        /**
         *    Holding the singleton instance
         */
        private static $_instance = null;

        /**
         * @return PolylangPostClonerWatchMeta
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         *    Prevent from creating more instances
         */
        private function __clone()
        {
        }

        /**
         */
        private function __construct()
        {
            add_filter('pll_copy_post_metas', array($this, 'copy_metas'), 20, 4);
            add_filter('pll_copy_term_metas', array($this, 'copy_metas'), 20, 4);
            add_action('acf/render_field_settings', array($this, 'action_acf_create_field_options'), 10, 1);
            add_action('acf/render_field', array($this, 'action_acf_create_field'), 10, 1);
            // add_action('init', array($this, 'register_strings'));
        }

        public function action_acf_create_field($field)
        {
            if (get_post_type() != 'post') {
                return; // FIXME
            }
            $sync = isset($field['lang_sync']) ? $field['lang_sync'] : 1;
            if ($sync) {
                echo '<small>Synced between languages</small>';
            }
        }
        /**
         * Register Field Strings so they can be translated
         *
         * @param $field
         * @param $post_id
         */
        public function register_strings()
        {
            if (!PLL_ADMIN) {
                return;
            }
            $return = acf_get_field_groups();
            foreach($return as $group) {
                $lang = pll_get_post_language($group['ID']);
                if ($lang != 'en') {
                    continue;
                }
                pll_register_string('group_' . $group['ID'] . '_title', $group['title']);

                $fields = acf_get_fields($group['ID']);
                foreach($fields as $field) {
                    pll_register_string($field['key'] . '_label', $field['label']);
                    if (!isset($field['choices'])) {
                        continue;
                    }
                    foreach($field['choices'] as $key=>$choiceValue) {
                        pll_register_string($field['key'] . '_label_choice_' . $key, $choiceValue);
                    }
                }
            }
        }

        /**
         * Handle Post language sync option
         *
         * @action 'pll_copy_post_metas'
         * @action 'pll_copy_term_metas'
         */
        public function copy_metas($keys, $sync, $from, $to)
        {
            foreach ($keys as $index => $key) {
                $field = get_field_object($key, $from);
                if (!$field) { // no ACF field
                    continue;
                }
                $sync = isset($field['lang_sync']) ? $field['lang_sync'] : 0;
                if (!$sync) {
                    unset($keys[$index]);
                }
            }
            return $keys;
        }

        /**
         * Add option to ACF fields about sync
         *
         * @param $field
         */
        public function action_acf_create_field_options($field)
        {
            acf_render_field_setting($field, array(
                'label' => __('Sync Field between Languages'),
                'instructions' => '',
                'type' => 'radio',
                'name' => 'lang_sync',
                'value' => isset($field['lang_sync']) ? $field['lang_sync'] : 0,
                'choices' => array(
                    1 => __('Yes', 'acf'),
                    0 => __('No', 'acf'),
                ),
                'layout' => 'horizontal',
            ), true);
        }
    }

endif;

