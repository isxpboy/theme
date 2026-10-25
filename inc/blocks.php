<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    wp_register_script( 'lei-notes-editor', get_template_directory_uri() . '/assets/editor.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-hooks', 'wp-compose', 'wp-server-side-render', 'wp-i18n' ), lei_notes_asset_version( 'assets/editor.js' ), true );
    $definitions = array();
    foreach ( glob( get_template_directory() . '/blocks/*/block.json' ) as $file ) {
        $metadata = json_decode( file_get_contents( $file ), true );
        $definitions[] = $metadata;
        register_block_type( dirname( $file ), array( 'render_callback' => 'lei_notes_render_block' ) );
    }
    wp_add_inline_script( 'lei-notes-editor', 'window.LeiBlockDefinitions=' . wp_json_encode( $definitions ) . ';', 'before' );
} );
add_action( 'enqueue_block_editor_assets', function () { wp_enqueue_script( 'lei-notes-editor' ); } );

function lei_notes_svg( $name ) {
    $paths = array(
        'speaker' => '<path d="m9 4-4 3H2v6h3l4 3V4Z"/><path d="M12 7c1.6 1.6 1.6 4.4 0 6m2.7-8.7a8 8 0 0 1 0 11.4"/>',
        'auto' => '<rect x="3" y="4" width="14" height="10" rx="2"/><path d="M7 17h6m-3-3v3"/>',
        'light' => '<circle cx="10" cy="10" r="3.2"/><path d="M10 2v1.4M10 16.6V18M2 10h1.4M16.6 10H18M4.3 4.3l1 1m9.4 9.4 1 1M4.3 15.7l1-1m9.4-9.4 1-1"/>',
        'dark' => '<path d="M16.8 12.2A7.2 7.2 0 0 1 7.8 3.2a7.2 7.2 0 1 0 9 9Z"/>',
        'github' => '<path d="M10 2a8 8 0 0 0-2.5 15.6v-2.5c-2 .4-2.6-1-2.6-1-.4-.8-.8-1-1-1 .9-.2 1.2.7 1.2.7.7 1.1 1.8.8 2.3.6.1-.5.3-.9.5-1.1-1.8-.2-3.6-.9-3.6-4 0-.9.3-1.6.8-2.1-.1-.2-.4-1 .1-2.1 0 0 .7-.2 2.2.8a7.7 7.7 0 0 1 4 0c1.5-1 2.2-.8 2.2-.8.5 1.1.2 1.9.1 2.1.5.5.8 1.2.8 2.1 0 3.1-1.9 3.8-3.7 4 .3.3.6.8.6 1.5v2.8A8 8 0 0 0 10 2Z"/>',
        'x' => '<path d="m4 3 12 14h-3L1 3h3Zm-1 14L17 3" transform="translate(1 0)"/>',
        'instagram' => '<rect x="3" y="3" width="14" height="14" rx="4"/><circle cx="10" cy="10" r="3.5"/><circle cx="14.5" cy="5.5" r=".6"/>',
        'rss' => '<circle cx="4" cy="16" r="1"/><path d="M3 9a8 8 0 0 1 8 8M3 3a14 14 0 0 1 14 14"/>',
        'map' => '<rect x="8" y="2" width="5" height="4" rx="1"/><rect x="2" y="14" width="5" height="4" rx="1"/><rect x="14" y="14" width="5" height="4" rx="1"/><path d="M10.5 6v4M4.5 14v-4h12v4"/>',
    );
    return '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

function lei_notes_reading_time( $post_id ) {
    $text = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) );
    preg_match_all( '/\p{Han}|[\p{L}\p{N}]+/u', $text, $matches );
    return max( 1, (int) ceil( count( $matches[0] ) / 350 ) );
}

function lei_notes_render_block( $attributes, $content, $block ) {
    $name = str_replace( 'lei-notes/', '', $block->name );
    $post_id = absint( $block->context['postId'] ?? get_the_ID() );
    $wrapper = get_block_wrapper_attributes( array( 'class' => 'lei-' . $name ) );
    $html = '';
    switch ( $name ) {
        case 'brand':
            $variant = $attributes['variant'] ?? 'signature';
            if ( 'custom-logo' === $variant && has_custom_logo() ) $html = get_custom_logo();
            else {
                $label = 'text' === $variant ? esc_html( get_bloginfo( 'name' ) ) : file_get_contents( get_template_directory() . '/assets/logo.svg' );
                $html = '<a class="brand ' . ( empty( $attributes['animated'] ) ? 'lei-logo-still' : '' ) . '" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( get_bloginfo( 'name' ) . ' 首页' ) . '">' . $label . '</a>';
            }
            break;
        case 'appearance':
            $html = '<details class="appearance-picker"><summary aria-label="外观设置"><span class="appearance-icon">' . lei_notes_svg( 'auto' ) . '</span><span>' . esc_html( $attributes['label'] ?? '外观' ) . '</span></summary><div class="appearance-menu" role="menu" aria-label="页面外观">';
            foreach ( array( 'auto' => '跟随系统', 'light' => '白天模式', 'dark' => '黑夜模式' ) as $mode => $label ) $html .= '<button type="button" role="menuitemradio" aria-label="' . esc_attr( $label ) . '" aria-checked="' . ( 'auto' === $mode ? 'true' : 'false' ) . '" data-appearance-choice="' . esc_attr( $mode ) . '">' . lei_notes_svg( $mode ) . '<span>' . esc_html( $label ) . '</span><span class="appearance-check" aria-hidden="true">✓</span></button>';
            $html .= '</div></details>';
            break;
        case 'post-meta':
            if ( ! $post_id ) return '';
            $html = '<div class="post-meta">';
            if ( ! empty( $attributes['showCategory'] ) ) {
                $categories = get_the_category( $post_id );
                if ( $categories ) $html .= '<a class="category-label" href="' . esc_url( get_category_link( $categories[0] ) ) . '">' . esc_html( $categories[0]->name ) . '</a><span class="meta-dot">/</span>';
            }
            if ( ! empty( $attributes['showDate'] ) ) $html .= '<time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( '', $post_id ) ) . '</time>';
            if ( ! empty( $attributes['showReadingTime'] ) ) $html .= '<span class="meta-dot">·</span><span>阅读约 ' . esc_html( lei_notes_reading_time( $post_id ) ) . ' 分钟</span>';
            if ( ! empty( $attributes['showReader'] ) && lei_notes_setting( 'reading' ) && is_singular() && ! post_password_required( $post_id ) ) $html .= '<div class="reading-controls"><button class="read-aloud" type="button" aria-label="朗读文章" aria-pressed="false">' . lei_notes_svg( 'speaker' ) . '<span>朗读</span></button><button class="stop-reading" type="button" aria-label="停止朗读" hidden>■</button><span class="reading-status" role="status" aria-live="polite"></span></div>';
            $html .= '</div>';
            break;
        case 'toc':
            if ( ! $post_id || post_password_required( $post_id ) ) return '';
            $items = array();
            lei_notes_headings( get_post_field( 'post_content', $post_id ), $items );
            if ( count( $items ) < 2 ) return '';
            $html = '<details class="article-toc" ' . ( ! empty( $attributes['open'] ) ? 'open' : '' ) . '><summary><span>' . esc_html( $attributes['title'] ?? '本文目录' ) . '</span><span class="toc-toggle" aria-hidden="true">⌃</span></summary><nav aria-label="本文目录">';
            foreach ( $items as $index => $item ) $html .= '<a class="toc-level-' . (int) $item['level'] . '" href="#' . esc_attr( rawurlencode( $item['id'] ) ) . '"><span class="toc-number">' . esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span>' . esc_html( $item['text'] ) . '</a>';
            $html .= '</nav></details>';
            break;
        case 'footer-links':
            $html = '<nav class="footer-links" aria-label="页脚链接">';
            foreach ( array( 'github' => 'GitHub', 'x' => 'Twitter / X', 'instagram' => 'Instagram' ) as $type => $label ) {
                $url = $attributes[ $type ] ?? '';
                if ( ! $url ) $url = lei_notes_setting( $type );
                $url = esc_url( $url, array( 'http', 'https' ) );
                $html .= $url ? '<a class="footer-icon" href="' . $url . '" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '" target="_blank" rel="me noopener noreferrer">' . lei_notes_svg( $type ) . '</a>' : '<span class="footer-icon is-placeholder" role="img" aria-label="' . esc_attr( $label . '，链接待填写' ) . '" title="' . esc_attr( $label . ' · 链接待填写' ) . '">' . lei_notes_svg( $type ) . '</span>';
            }
            $html .= '<span class="footer-link-divider" aria-hidden="true"></span>';
            if ( ! empty( $attributes['showRss'] ) ) $html .= '<a class="footer-icon" href="' . esc_url( get_feed_link() ) . '" aria-label="订阅文章 RSS" title="订阅文章 RSS">' . lei_notes_svg( 'rss' ) . '</a>';
            if ( ! empty( $attributes['showMap'] ) ) $html .= '<a class="footer-map-link" href="' . esc_url( add_query_arg( 'lei_sitemap', '1', home_url( '/' ) ) ) . '" aria-label="文章地图">' . lei_notes_svg( 'map' ) . '<span>文章地图</span></a>';
            $sitemap = get_option( 'blog_public' ) ? get_sitemap_url( 'index' ) : false;
            if ( ! empty( $attributes['showXml'] ) && $sitemap ) $html .= '<a class="footer-xml-link" href="' . esc_url( $sitemap ) . '" aria-label="XML 站点地图" title="XML 站点地图"><span class="xml-badge">XML</span></a>';
            $html .= '</nav>';
            break;
        case 'copyright':
            $html = '<span class="footer-copyright">© ' . esc_html( wp_date( 'Y' ) ) . ' ' . esc_html( $attributes['name'] ?: get_bloginfo( 'name' ) ) . '</span>';
            break;
        case 'category-filter':
            $html = '<nav class="category-links" aria-label="文章分类"><a ' . ( is_home() ? 'aria-current="page"' : '' ) . ' href="' . esc_url( get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ) . '">全部文章</a>';
            $terms = get_categories( array( 'hide_empty' => true, 'number' => 12 ) );
            foreach ( $terms as $term ) $html .= '<a ' . ( is_category( $term->term_id ) ? 'aria-current="page"' : '' ) . ' href="' . esc_url( get_category_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
            $html .= '</nav>';
            break;
        case 'article-map':
            $page = max( 1, absint( get_query_var( 'lei_map_page' ) ) );
            $query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => max( 10, min( 200, absint( $attributes['perPage'] ?? 100 ) ) ), 'paged' => $page, 'orderby' => 'date', 'order' => 'DESC' ) );
            $html = '<div class="sitemap-group"><ul>';
            foreach ( $query->posts as $entry ) $html .= '<li><a href="' . esc_url( get_permalink( $entry ) ) . '">' . esc_html( get_the_title( $entry ) ?: '未命名文章' ) . '</a><time datetime="' . esc_attr( get_the_date( 'c', $entry ) ) . '">' . esc_html( get_the_date( '', $entry ) ) . '</time></li>';
            if ( ! $query->posts ) $html .= '<li>暂时还没有公开文章。</li>';
            $html .= '</ul></div>';
            if ( $query->max_num_pages > 1 ) $html .= '<nav class="lei-pagination" aria-label="文章地图分页">' . paginate_links( array( 'base' => add_query_arg( 'lei_map_page', '%#%', add_query_arg( 'lei_sitemap', '1', home_url( '/' ) ) ), 'format' => '', 'current' => $page, 'total' => $query->max_num_pages ) ) . '</nav>';
            if ( ! empty( $attributes['showPages'] ) ) {
                $html .= '<section class="sitemap-group"><h2>站点页面</h2><ul>';
                $pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                foreach ( $pages as $entry ) $html .= '<li><a href="' . esc_url( get_permalink( $entry ) ) . '">' . esc_html( get_the_title( $entry ) ) . '</a></li>';
                $html .= '</ul></section>';
            }
            break;
    }
    return '<div ' . $wrapper . '>' . $html . '</div>';
}
