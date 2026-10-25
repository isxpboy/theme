<?php
/** Theme bootstrap. */
defined( 'ABSPATH' ) || exit;
define( 'LEI_NOTES_VERSION', '1.0.0' );
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/seo.php';

add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'lei-notes', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_editor_style( array( 'assets/theme.css', 'assets/editor.css' ) );
    register_block_pattern_category( 'lei-notes', array( 'label' => 'Lei · 开发者博客' ) );
} );

function lei_notes_asset_version( $file ) {
    $path = get_template_directory() . '/' . $file;
    return file_exists( $path ) ? (string) filemtime( $path ) : LEI_NOTES_VERSION;
}

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'lei-notes', get_template_directory_uri() . '/assets/theme.css', array(), lei_notes_asset_version( 'assets/theme.css' ) );
    wp_enqueue_style( 'lei-notes-wordpress', get_template_directory_uri() . '/assets/wordpress.css', array( 'lei-notes' ), lei_notes_asset_version( 'assets/wordpress.css' ) );
    wp_enqueue_script( 'lei-notes-appearance', get_template_directory_uri() . '/assets/appearance.js', array(), lei_notes_asset_version( 'assets/appearance.js' ), false );
    $settings = array(
        'appearance' => lei_notes_setting( 'appearance' ),
        'reading' => (bool) lei_notes_setting( 'reading' ),
        'code' => (bool) lei_notes_setting( 'code' ),
        'lineNumbers' => (bool) lei_notes_setting( 'line_numbers' ),
        'imageZoom' => (bool) lei_notes_setting( 'image_zoom' ),
        'searchUrl' => home_url( '/' ),
    );
    wp_add_inline_script( 'lei-notes-appearance', 'window.LeiThemeSettings=' . wp_json_encode( $settings ) . ';', 'before' );
    if ( is_singular() && lei_notes_setting( 'code' ) ) {
        $languages = array( 'core', 'markup', 'css', 'clike', 'javascript', 'typescript', 'yaml', 'bash', 'json', 'python', 'markup-templating', 'php', 'go', 'sql' );
        $previous = array();
        foreach ( $languages as $language ) {
            $handle = 'lei-prism-' . $language;
            wp_enqueue_script( $handle, get_template_directory_uri() . '/assets/vendor/prism-' . $language . '.min.js', $previous, LEI_NOTES_VERSION, true );
            if ( 'core' === $language ) wp_add_inline_script( $handle, 'window.Prism={manual:true};', 'before' );
            $previous = array( $handle );
        }
    }
    wp_enqueue_script( 'lei-notes-front', get_template_directory_uri() . '/assets/front.js', array( 'lei-notes-appearance' ), lei_notes_asset_version( 'assets/front.js' ), true );
} );

add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'lei-notes-site';
    if ( ! lei_notes_setting( 'sticky_header' ) ) $classes[] = 'lei-header-static';
    if ( ! lei_notes_setting( 'logo_animation' ) ) $classes[] = 'lei-logo-still';
    return $classes;
} );

add_action( 'wp_footer', function () {
    if ( ! lei_notes_setting( 'back_to_top' ) ) return;
    echo '<a class="back-to-top" href="#lei-top" aria-label="回到顶部" title="回到顶部"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 11 5-5 5 5M12 6v13"/></svg></a>';
} );

add_filter( 'query_vars', function ( $vars ) { $vars[] = 'lei_sitemap'; $vars[] = 'lei_map_page'; return $vars; } );
add_action( 'template_redirect', function () {
    if ( ! get_query_var( 'lei_sitemap' ) ) return;
    remove_action( 'template_redirect', 'redirect_canonical' );
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 1 );
add_filter( 'template_include', function ( $template ) {
    if ( ! get_query_var( 'lei_sitemap' ) ) return $template;
    $block_template = get_block_template( get_stylesheet() . '//page-article-map', 'wp_template' );
    if ( ! $block_template ) return $template;
    $GLOBALS['_wp_current_template_content'] = $block_template->content;
    $GLOBALS['_wp_current_template_id'] = $block_template->id;
    return ABSPATH . WPINC . '/template-canvas.php';
}, 99 );
add_filter( 'pre_get_document_title', function ( $title ) { return get_query_var( 'lei_sitemap' ) ? '文章地图 · ' . get_bloginfo( 'name' ) : $title; } );

function lei_notes_headings( $content, &$items = array() ) {
    $seen = array();
    return preg_replace_callback( '/<h([23])\b([^>]*)>([\s\S]*?)<\/h\1>/i', function ( $match ) use ( &$items, &$seen ) {
        $text = trim( wp_strip_all_tags( $match[3] ) );
        if ( '' === $text ) return $match[0];
        if ( preg_match( '/\bid\s*=\s*([\'"])(.*?)\1/i', $match[2], $id_match ) ) $id = html_entity_decode( $id_match[2], ENT_QUOTES, 'UTF-8' );
        else {
            $base = 'heading-' . substr( md5( $text ), 0, 10 );
            $seen[ $base ] = ( $seen[ $base ] ?? 0 ) + 1;
            $id = $base . ( $seen[ $base ] > 1 ? '-' . $seen[ $base ] : '' );
        }
        $items[] = array( 'id' => $id, 'text' => $text, 'level' => (int) $match[1] );
        return preg_match( '/(?:^|\s)id\s*=/i', $match[2] ) ? $match[0] : '<h' . $match[1] . $match[2] . ' id="' . esc_attr( $id ) . '">' . $match[3] . '</h' . $match[1] . '>';
    }, $content );
}
add_filter( 'the_content', function ( $content ) { return ! is_admin() && is_singular() && ! post_password_required() ? lei_notes_headings( $content ) : $content; }, 20 );

add_filter( 'register_block_type_args', function ( $args, $name ) {
    if ( 'core/code' === $name ) {
        $args['attributes']['leiLanguage'] = array( 'type' => 'string', 'default' => 'plain' );
        $args['attributes']['leiFilename'] = array( 'type' => 'string', 'default' => '' );
    }
    return $args;
}, 10, 2 );
add_filter( 'render_block_core/code', function ( $html, $block ) {
    $language = sanitize_key( $block['attrs']['leiLanguage'] ?? 'plain' );
    if ( ! in_array( $language, array( 'plain', 'javascript', 'typescript', 'yaml', 'bash', 'json', 'python', 'php', 'markup', 'css', 'go', 'sql' ), true ) ) $language = 'plain';
    $processor = new WP_HTML_Tag_Processor( $html );
    if ( $processor->next_tag( 'PRE' ) && ! empty( $block['attrs']['leiFilename'] ) ) $processor->set_attribute( 'data-lei-filename', sanitize_text_field( $block['attrs']['leiFilename'] ) );
    if ( $processor->next_tag( 'CODE' ) ) { $processor->add_class( 'language-' . $language ); $html = $processor->get_updated_html(); }
    return $html;
}, 10, 2 );
