<?php
defined( 'ABSPATH' ) || exit;
function lei_notes_owns_seo() {
    return lei_notes_setting( 'seo' ) && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'AIOSEO_VERSION' ) && ! defined( 'SEOPRESS_VERSION' );
}
add_action( 'wp', function () { if ( lei_notes_owns_seo() ) remove_action( 'wp_head', 'rel_canonical' ); } );
add_action( 'wp_head', function () {
    if ( ! lei_notes_owns_seo() || is_404() || is_search() || is_preview() || is_feed() ) return;
    $map = (bool) get_query_var( 'lei_sitemap' );
    $singular = is_singular() && ! $map;
    $id = $singular ? get_queried_object_id() : 0;
    $canonical = $map ? add_query_arg( 'lei_sitemap', 1, home_url( '/' ) ) : ( $singular ? wp_get_canonical_url( $id ) : get_pagenum_link( max( 1, get_query_var( 'paged' ) ) ) );
    if ( ! $canonical ) return;
    $description = get_bloginfo( 'description' );
    if ( $map ) $description = '全部公开文章与站点页面的阅读索引。';
    elseif ( $singular ) {
        $description = post_password_required( $id ) ? '这是一篇受密码保护的文章。' : get_post_field( 'post_excerpt', $id );
        if ( ! $description ) $description = get_post_field( 'post_content', $id );
        $description = wp_html_excerpt( wp_strip_all_tags( strip_shortcodes( $description ) ), 160, '…' );
    } elseif ( is_category() || is_tag() || is_tax() ) $description = wp_strip_all_tags( term_description() ) ?: $description;
    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:type" content="' . ( is_single() ? 'article' : 'website' ) . '"><meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '"><meta property="og:description" content="' . esc_attr( $description ) . '"><meta property="og:url" content="' . esc_url( $canonical ) . '"><meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    $schema = array( '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ), 'inLanguage' => get_bloginfo( 'language' ) );
    if ( is_single() && ! post_password_required( $id ) && 'publish' === get_post_status( $id ) ) {
        $schema = array( '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => get_the_title( $id ), 'description' => $description, 'url' => $canonical, 'mainEntityOfPage' => $canonical, 'datePublished' => get_the_date( 'c', $id ), 'dateModified' => get_post_modified_time( 'c', false, $id ), 'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ), 'url' => get_author_posts_url( get_post_field( 'post_author', $id ) ) ), 'inLanguage' => get_bloginfo( 'language' ) );
        if ( has_post_thumbnail( $id ) ) { $image = get_the_post_thumbnail_url( $id, 'large' ); $schema['image'] = array( $image ); echo '<meta property="og:image" content="' . esc_url( $image ) . '">'; }
    }
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );
add_filter( 'wp_robots', function ( $robots ) { if ( is_search() || is_404() || is_preview() ) { unset( $robots['index'] ); $robots['noindex'] = true; } return $robots; } );
