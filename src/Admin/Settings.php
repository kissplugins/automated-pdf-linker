<?php
/**
 * Admin Settings
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Settings Class
 *
 * Handles plugin settings registration and management.
 *
 * @since 3.0.0
 */
class Settings {

    /**
     * Settings option name.
     */
    const SETTINGS_OPTION_NAME = 'kapl_settings';

    /**
     * Settings page slug.
     */
    const SETTINGS_SLUG = 'kiss-pdf-linker-settings';

    /**
     * Index builder instance.
     *
     * @var IndexBuilder
     */
    private $index_builder;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param IndexBuilder $index_builder Index builder instance.
     * @param Logger       $logger        Logger instance.
     */
    public function __construct( IndexBuilder $index_builder, Logger $logger ) {
        $this->index_builder = $index_builder;
        $this->logger        = $logger;
    }

    /**
     * Register plugin settings using the WordPress Settings API.
     *
     * @return void
     */
    public function register_settings(): void {
        // Register the main setting group and option name
        \register_setting(
            'kapl_settings_group',
            self::SETTINGS_OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        $this->register_directory_section();
        $this->register_appearance_section();
        $this->register_matching_section();
        $this->register_debug_section();
    }

    /**
     * Register directory selection section.
     *
     * @return void
     */
    private function register_directory_section(): void {
        \add_settings_section(
            'kapl_directory_selection_section',
            \__( 'Select Directories to Scan', 'kiss-automated-pdf-linker' ),
            [ $this, 'directory_selection_section_callback' ],
            self::SETTINGS_SLUG
        );

        \add_settings_field(
            'kapl_selected_directories',
            \__( 'Scan these folders:', 'kiss-automated-pdf-linker' ),
            [ $this, 'selected_directories_field_callback' ],
            self::SETTINGS_SLUG,
            'kapl_directory_selection_section'
        );
    }

    /**
     * Register appearance section.
     *
     * @return void
     */
    private function register_appearance_section(): void {
        add_settings_section(
            'kapl_appearance_section',
            __( 'PDF Link Appearance', 'kiss-automated-pdf-linker' ),
            [ $this, 'appearance_section_callback' ],
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'kapl_link_color',
            __( 'PDF Link Color:', 'kiss-automated-pdf-linker' ),
            [ $this, 'link_color_field_callback' ],
            self::SETTINGS_SLUG,
            'kapl_appearance_section'
        );
    }

    /**
     * Register PDF matching section.
     *
     * @return void
     */
    private function register_matching_section(): void {
        add_settings_section(
            'kapl_pdf_matching_section',
            __( 'PDF Matching Settings', 'kiss-automated-pdf-linker' ),
            '',
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'kapl_use_product_title_match',
            __( 'Use product title for file match:', 'kiss-automated-pdf-linker' ),
            [ $this, 'use_product_title_field_callback' ],
            self::SETTINGS_SLUG,
            'kapl_pdf_matching_section'
        );
    }

    /**
     * Register debug section.
     *
     * @return void
     */
    private function register_debug_section(): void {
        \add_settings_section(
            'kapl_debug_section',
            \__( 'Debugging', 'kiss-automated-pdf-linker' ),
            [ $this, 'debug_section_callback' ],
            self::SETTINGS_SLUG
        );

        \add_settings_field(
            'kapl_debug_logging',
            \__( 'Enable debug logging', 'kiss-automated-pdf-linker' ),
            [ $this, 'debug_logging_field_callback' ],
            self::SETTINGS_SLUG,
            'kapl_debug_section'
        );
    }



    /**
     * Sanitize settings before saving.
     *
     * @param array|mixed $input Raw input data from the settings form.
     * @return array Sanitized settings array.
     */
    public function sanitize_settings( $input ): array {
        $sanitized_input = [];

        // Sanitize selected directories
        if ( isset( $input['selected_directories'] ) && is_array( $input['selected_directories'] ) ) {
            $sanitized_input['selected_directories'] = array_map( 'sanitize_text_field', $input['selected_directories'] );
        } else {
            $sanitized_input['selected_directories'] = [];
        }

        // Sanitize link color
        if ( isset( $input['link_color'] ) ) {
            $color = sanitize_hex_color( $input['link_color'] );
            $sanitized_input['link_color'] = $color ? $color : '#0000FF';
        } else {
            $sanitized_input['link_color'] = '#0000FF';
        }

        // Sanitize checkboxes
        $sanitized_input['use_product_title_match'] = isset( $input['use_product_title_match'] );
        $sanitized_input['debug_logging'] = isset( $input['debug_logging'] );

        return $sanitized_input;
    }

    /**
     * Get plugin settings.
     *
     * @return array Plugin settings with defaults.
     */
    public function get_settings(): array {
        return get_option( self::SETTINGS_OPTION_NAME, [
            'selected_directories'     => [],
            'link_color'              => '#0000FF',
            'use_product_title_match' => false,
            'debug_logging'           => false,
        ] );
    }

    /**
     * Directory selection section callback.
     *
     * @return void
     */
    public function directory_selection_section_callback(): void {
        echo '<p>' . esc_html__( 'Check the top-level folders within your uploads directory that you want to scan recursively for PDF files.', 'kiss-automated-pdf-linker' ) . '</p>';
    }

    /**
     * Selected directories field callback.
     *
     * @return void
     */
    public function selected_directories_field_callback(): void {
        $settings = $this->get_settings();
        $selected_dirs = $settings['selected_directories'];
        $available_dirs = $this->index_builder->get_available_directories();

        if ( empty( $available_dirs ) ) {
            echo '<p>' . esc_html__( 'No subdirectories found directly within the uploads folder.', 'kiss-automated-pdf-linker' ) . '</p>';
            return;
        }

        echo '<fieldset>';
        foreach ( $available_dirs as $dir_name ) {
            $field_id = 'kapl_dir_' . esc_attr( $dir_name );
            $checked = in_array( $dir_name, $selected_dirs, true ) ? 'checked' : '';
            ?>
            <label for="<?php echo esc_attr( $field_id ); ?>">
                <input
                    type="checkbox"
                    id="<?php echo esc_attr( $field_id ); ?>"
                    name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[selected_directories][]"
                    value="<?php echo esc_attr( $dir_name ); ?>"
                    <?php echo esc_attr( $checked ); ?>
                />
                <code><?php echo esc_html( $dir_name ); ?>/</code>
            </label><br>
            <?php
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__( 'Checking a folder will include all PDFs within it and its subfolders in the index.', 'kiss-automated-pdf-linker' ) . '</p>';
    }

    /**
     * Appearance section callback.
     *
     * @return void
     */
    public function appearance_section_callback(): void {
        echo '<p>' . esc_html__( 'Customize the appearance of PDF links generated by the shortcode.', 'kiss-automated-pdf-linker' ) . '</p>';
    }

    /**
     * Link color field callback.
     *
     * @return void
     */
    public function link_color_field_callback(): void {
        $settings = $this->get_settings();
        $link_color = $settings['link_color'];
        ?>
        <input 
            type="text" 
            name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[link_color]" 
            id="kapl_link_color" 
            value="<?php echo esc_attr( $link_color ); ?>" 
            class="kapl-color-picker" 
        />
        <p class="description">
            <?php esc_html_e( 'Choose the color for PDF links. This will be applied to all links with the kapl-pdf-link class.', 'kiss-automated-pdf-linker' ); ?>
        </p>
        <?php
    }

    /**
     * Use product title field callback.
     *
     * @return void
     */
    public function use_product_title_field_callback(): void {
        $settings = $this->get_settings();
        $use_product_title_match = $settings['use_product_title_match'];
        ?>
        <label for="kapl_use_product_title_match">
            <input 
                type="checkbox" 
                name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[use_product_title_match]" 
                id="kapl_use_product_title_match" 
                value="1" 
                <?php checked( $use_product_title_match, true ); ?>
            />
            <?php esc_html_e( 'Enable automatic PDF linking for strains in the product tab.', 'kiss-automated-pdf-linker' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'When enabled, this will attempt to automatically link strain names listed in the \'Strains\' product tab to matching PDF files.', 'kiss-automated-pdf-linker' ); ?>
        </p>
        <?php
    }

    /**
     * Debug section callback.
     *
     * @return void
     */
    public function debug_section_callback(): void {
        echo '<p>' . esc_html__( 'Toggle debug output to the PHP error log.', 'kiss-automated-pdf-linker' ) . '</p>';
    }

    /**
     * Debug logging field callback.
     *
     * @return void
     */
    public function debug_logging_field_callback(): void {
        $settings = $this->get_settings();
        $debug_logging = $settings['debug_logging'];
        ?>
        <label for="kapl_debug_logging">
            <input
                type="checkbox"
                name="<?php echo esc_attr( self::SETTINGS_OPTION_NAME ); ?>[debug_logging]"
                id="kapl_debug_logging"
                value="1"
                <?php checked( $debug_logging, true ); ?>
            />
            <?php esc_html_e( 'Write debugging information to the PHP error log.', 'kiss-automated-pdf-linker' ); ?>
        </label>
        <?php
    }





    /**
     * Handle index rebuilding if requested.
     *
     * @return void
     */
    public function handle_index_rebuild(): void {
        if ( ! isset( $_POST['kapl_rebuild_index_nonce'], $_POST['kapl_rebuild_index'] ) ||
             ! \wp_verify_nonce( \sanitize_key( $_POST['kapl_rebuild_index_nonce'] ), 'kapl_rebuild_index_action' ) ) {
            return;
        }

        $settings = $this->get_settings();
        $result = $this->index_builder->build_index( $settings['selected_directories'] );

        if ( \is_wp_error( $result ) ) {
            \add_settings_error(
                'kapl_rebuild_status',
                'rebuild_error',
                \esc_html__( 'Error rebuilding index: ', 'kiss-automated-pdf-linker' ) . $result->get_error_message(),
                'error'
            );
            $this->logger->error( 'Error rebuilding index - ' . $result->get_error_message() );
        } else {
            $rebuild_message = sprintf(
                /* translators: %d: number of PDF files indexed */
                \esc_html__( 'PDF index rebuilt successfully. Found %d PDF files.', 'kiss-automated-pdf-linker' ),
                (int) $result
            );
            \add_settings_error( 'kapl_rebuild_status', 'rebuild_success', $rebuild_message, 'updated' );
            $this->logger->info( "PDF index rebuilt successfully. Found {$result} PDF files." );
        }
    }

    /**
     * Get index builder instance.
     *
     * @return IndexBuilder
     */
    public function get_index_builder(): IndexBuilder {
        return $this->index_builder;
    }
}
