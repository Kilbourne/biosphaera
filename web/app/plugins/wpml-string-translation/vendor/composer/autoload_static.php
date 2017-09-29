<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9ffae65f3fc7003fd8b956c13c3d67d4
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $prefixesPsr0 = array (
        'x' => 
        array (
            'xrstf\\Composer52' => 
            array (
                0 => __DIR__ . '/..' . '/xrstf/composer-php52/lib',
            ),
        ),
        'T' => 
        array (
            'Twig_' => 
            array (
                0 => __DIR__ . '/..' . '/twig/twig/lib',
            ),
        ),
    );

    public static $classMap = array (
        'IWPML_PB_Strategy' => __DIR__ . '/../..' . '/classes/page-builders/strategy/interface-iwpml-pb-strategy.php',
        'IWPML_ST_Page_Translations_Persist' => __DIR__ . '/../..' . '/classes/filters/db-cache/persist/interface-iwpml-st-page-translations-persist.php',
        'IWPML_St_Upgrade_Command' => __DIR__ . '/../..' . '/classes/upgrade/interface-iwpml_st_upgrade_command.php',
        'WPML_Admin_Notifier' => __DIR__ . '/../..' . '/classes/class-wpml-admin-notifier.php',
        'WPML_Admin_Text_Configuration' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-configuration.php',
        'WPML_Admin_Text_Functionality' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-functionality.class.php',
        'WPML_Admin_Text_Import' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-import.class.php',
        'WPML_Admin_Texts' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-texts.class.php',
        'WPML_Auto_Loader' => __DIR__ . '/..' . '/wpml/commons/src/wpml-auto-loader.php',
        'WPML_Autoregister_Context_Exclude' => __DIR__ . '/../..' . '/classes/filters/autoregister/class-wpml-autoregister-context-exclude.php',
        'WPML_Autoregister_Save_Strings' => __DIR__ . '/../..' . '/classes/filters/autoregister/class-wpml-autoregister-save-strings.php',
        'WPML_Change_String_Domain_Language_Dialog' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-change-string-domain-language-dialog.php',
        'WPML_Change_String_Language_Dialog' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-change-string-language-dialog.php',
        'WPML_Custom_Post_Slug_UI' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-custom-post-slug-ui.php',
        'WPML_Dependencies' => __DIR__ . '/..' . '/wpml/commons/src/dependencies/class-wpml-dependencies.php',
        'WPML_Displayed_String_Filter' => __DIR__ . '/../..' . '/classes/filters/class-wpml-displayed-string-filter.php',
        'WPML_File_Name_Converter' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-file-name-converter.php',
        'WPML_Language_Of_Domain' => __DIR__ . '/../..' . '/classes/class-wpml-language-of-domain.php',
        'WPML_Localization' => __DIR__ . '/../..' . '/inc/wpml-localization.class.php',
        'WPML_PB_API_Hooks_Strategy' => __DIR__ . '/../..' . '/classes/page-builders/strategy/api-hooks/class-wpml-pb-api-hooks-strategy.php',
        'WPML_PB_Config_Import_Shortcode' => __DIR__ . '/../..' . '/classes/page-builders/strategy/shortcode/class-wpml-pb-config-import-shortcode.php',
        'WPML_PB_Factory' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-factory.php',
        'WPML_PB_Integration' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-integration.php',
        'WPML_PB_Integration_Rescan' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-rescan.php',
        'WPML_PB_Loader' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-loader.php',
        'WPML_PB_Register_Shortcodes' => __DIR__ . '/../..' . '/classes/page-builders/strategy/shortcode/class-wpml-pb-register-shortcodes.php',
        'WPML_PB_Shortcode_Strategy' => __DIR__ . '/../..' . '/classes/page-builders/strategy/shortcode/class-wpml-pb-shortcode-strategy.php',
        'WPML_PB_Shortcodes' => __DIR__ . '/../..' . '/classes/page-builders/strategy/shortcode/class-wpml-pb-shortcodes.php',
        'WPML_PB_String_Registration' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-string-registration.php',
        'WPML_PB_String_Translation' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-string-translation.php',
        'WPML_PB_Update_API_Hooks_In_Content' => __DIR__ . '/../..' . '/classes/page-builders/strategy/api-hooks/class-wpml-pb-update-api-hooks-in-content.php',
        'WPML_PB_Update_Post' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-update-post.php',
        'WPML_PB_Update_Shortcodes_In_Content' => __DIR__ . '/../..' . '/classes/page-builders/strategy/shortcode/class-wpml-pb-update-shortcodes-in-content.php',
        'WPML_PB_Update_Translated_Posts_From_Original' => __DIR__ . '/../..' . '/classes/page-builders/class-wpml-pb-update-translated-posts-from-original.php',
        'WPML_PO_Import' => __DIR__ . '/../..' . '/inc/gettext/wpml-po-import.class.php',
        'WPML_PO_Import_Strings' => __DIR__ . '/../..' . '/classes/po-import/class-wpml-po-import-strings.php',
        'WPML_PO_Import_Strings_Scripts' => __DIR__ . '/../..' . '/classes/po-import/class-wpml-po-import-strings-scripts.php',
        'WPML_PO_Parser' => __DIR__ . '/../..' . '/inc/gettext/wpml-po-parser.class.php',
        'WPML_Package' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package.class.php',
        'WPML_Package_Admin_Lang_Switcher' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-admin-lang-switcher.class.php',
        'WPML_Package_Exception' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-exception.class.php',
        'WPML_Package_Helper' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-helper.class.php',
        'WPML_Package_ST' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-st.class.php',
        'WPML_Package_TM' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-tm.class.php',
        'WPML_Package_TM_Jobs' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-tm-jobs.class.php',
        'WPML_Package_Translation' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation.class.php',
        'WPML_Package_Translation_HTML_Packages' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-html-packages.class.php',
        'WPML_Package_Translation_Metabox' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-metabox.class.php',
        'WPML_Package_Translation_Schema' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-schema.class.php',
        'WPML_Package_Translation_UI' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-ui.class.php',
        'WPML_Plugin_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-plugin-string-scanner.class.php',
        'WPML_Register_String_Filter' => __DIR__ . '/../..' . '/classes/filters/class-wpml-register-string-filter.php',
        'WPML_Rewrite_Rule_Filter' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-rewrite-rule-filter.php',
        'WPML_ST_Admin_Blog_Option' => __DIR__ . '/../..' . '/classes/admin-texts/class-wpml-st-admin-blog-option.php',
        'WPML_ST_Admin_Option_Translation' => __DIR__ . '/../..' . '/classes/admin-texts/class-wpml-st-admin-option-translation.php',
        'WPML_ST_Admin_String' => __DIR__ . '/../..' . '/classes/class-wpml-st-admin-string.php',
        'WPML_ST_Blog_Name_And_Description_Hooks' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-blog-name-and-description-hooks.php',
        'WPML_ST_DB_Cache' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-db-cache.php',
        'WPML_ST_DB_Cache_Factory' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-db-cache-factory.php',
        'WPML_ST_DB_Chunk_Retrieve' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-db-chunk-retrieve.php',
        'WPML_ST_DB_Mappers_String_Positions' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-db-mappers-string-positions.php',
        'WPML_ST_DB_Mappers_Strings' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-db-mappers-strings.php',
        'WPML_ST_DB_Translation_Retrieve' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-db-translation-retrieve.php',
        'WPML_ST_DB_Troubleshooting' => __DIR__ . '/../..' . '/classes/menus/class-wpml-st-db-troubleshooting.php',
        'WPML_ST_Domain_Fallback' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-domain-fallback.php',
        'WPML_ST_ICL_String_Translations' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-icl-string-translations.php',
        'WPML_ST_ICL_Strings' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-icl-strings.php',
        'WPML_ST_Label_Translation' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-label-translation.php',
        'WPML_ST_MO_Downloader' => __DIR__ . '/../..' . '/inc/auto-download-locales.php',
        'WPML_ST_Page_Translation' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-page-translation.php',
        'WPML_ST_Page_Translations' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-page-translations.php',
        'WPML_ST_Page_Translations_Cached_Persist' => __DIR__ . '/../..' . '/classes/filters/db-cache/persist/class-wpml-st-page-translations-cached-persist.php',
        'WPML_ST_Page_Translations_Persist' => __DIR__ . '/../..' . '/classes/filters/db-cache/persist/class-wpml-st-page-translations-persist.php',
        'WPML_ST_Page_URL_Preprocessor' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-page-url-preprocessor.php',
        'WPML_ST_Records' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-records.php',
        'WPML_ST_Reset' => __DIR__ . '/../..' . '/classes/class-wpml-st-reset.php',
        'WPML_ST_Settings' => __DIR__ . '/../..' . '/classes/class-wpml-st-settings.php',
        'WPML_ST_String' => __DIR__ . '/../..' . '/classes/class-wpml-st-string.php',
        'WPML_ST_String_Factory' => __DIR__ . '/../..' . '/classes/class-wpml-st-string-factory.php',
        'WPML_ST_String_Statuses' => __DIR__ . '/../..' . '/classes/class-wpml-st-string-statuses.php',
        'WPML_ST_String_Update' => __DIR__ . '/../..' . '/inc/wpml-st-string-update.class.php',
        'WPML_ST_Strings' => __DIR__ . '/../..' . '/classes/class-wpml-st-strings.php',
        'WPML_ST_TM_Jobs' => __DIR__ . '/../..' . '/classes/wpml-tm/class-wpml-st-tm-jobs.php',
        'WPML_ST_Themes_And_Plugins_Settings' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-themes-and-plugins-settings.php',
        'WPML_ST_Themes_And_Plugins_Updates' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-themes-and-plugins-updates.php',
        'WPML_ST_Upgrade' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade.php',
        'WPML_ST_Upgrade_Command_Factory' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-command-factory.php',
        'WPML_ST_Upgrade_Command_Not_Found_Exception' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-command-not-found-exception.php',
        'WPML_ST_Upgrade_DB_String_Packages' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-string-packages.php',
        'WPML_ST_Upgrade_Db_Cache_Command' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-cache-command.php',
        'WPML_ST_Upgrade_Display_Strings_Scan_Notices' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-display-strings-scan-notices.php',
        'WPML_ST_Upgrade_Migrate_Originals' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-migrate-originals.php',
        'WPML_ST_User_Fields' => __DIR__ . '/../..' . '/classes/class-wpml-st-user-fields.php',
        'WPML_ST_Verify_Dependencies' => __DIR__ . '/../..' . '/classes/class-wpml-st-verify-dependencies.php',
        'WPML_ST_WP_Loaded_Action' => __DIR__ . '/../..' . '/classes/actions/class-wpml-st-wp-loaded-action.php',
        'WPML_ST_WP_Wrapper' => __DIR__ . '/../..' . '/classes/filters/db-cache/class-wpml-st-wp-wrapper.php',
        'WPML_Slug_Translation' => __DIR__ . '/../..' . '/inc/slug-translation.php',
        'WPML_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-string-scanner.class.php',
        'WPML_String_Translation' => __DIR__ . '/../..' . '/inc/wpml-string-translation.class.php',
        'WPML_String_Translation_MO_Import' => __DIR__ . '/../..' . '/inc/gettext/wpml-string-translation-mo-import.class.php',
        'WPML_String_Translation_Table' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-string-translation-table.php',
        'WPML_TM_Filters' => __DIR__ . '/../..' . '/classes/filters/class-wpml-tm-filters.php',
        'WPML_TM_Widget_Filter' => __DIR__ . '/../..' . '/classes/filters/class-wpml-tm-widget-filter.php',
        'WPML_Theme_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-theme-string-scanner.class.php',
        'WPML_Twig_WP_Plugin_Extension' => __DIR__ . '/..' . '/wpml/commons/src/twig-extensions/wpml-twig-wp-plugin-extension.php',
        'WPML_post_slug_translation_settings' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-post-slug-translation-settings.php',
        'WP_Widget_Text_Icl' => __DIR__ . '/../..' . '/classes/widgets/wp-widget-text-icl.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9ffae65f3fc7003fd8b956c13c3d67d4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9ffae65f3fc7003fd8b956c13c3d67d4::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit9ffae65f3fc7003fd8b956c13c3d67d4::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit9ffae65f3fc7003fd8b956c13c3d67d4::$classMap;

        }, null, ClassLoader::class);
    }
}
