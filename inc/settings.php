<?php
defined( 'ABSPATH' ) || exit;
function lei_notes_defaults() {
    return array( 'appearance' => 'auto', 'logo_animation' => true, 'sticky_header' => true, 'reading' => true, 'code' => true, 'line_numbers' => true, 'image_zoom' => true, 'back_to_top' => true, 'seo' => true, 'github' => '', 'x' => '', 'instagram' => '' );
}
function lei_notes_setting( $key ) { $values = get_theme_mod( 'lei_notes_options', array() ); $defaults = lei_notes_defaults(); return $values[ $key ] ?? $defaults[ $key ] ?? null; }
function lei_notes_sanitize_options( $input ) {
    $clean = lei_notes_defaults();
    $input = is_array( $input ) ? $input : array();
    foreach ( $clean as $key => $default ) {
        if ( is_bool( $default ) ) $clean[ $key ] = ! empty( $input[ $key ] );
        elseif ( 'appearance' === $key ) $clean[ $key ] = in_array( $input[ $key ] ?? '', array( 'auto', 'light', 'dark' ), true ) ? $input[ $key ] : 'auto';
        else {
            $url = esc_url_raw( $input[ $key ] ?? '', array( 'https', 'http' ) );
            $clean[ $key ] = in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ? $url : '';
        }
    }
    return $clean;
}
add_action( 'admin_menu', function () { add_theme_page( 'Lei 主题设置', 'Lei 主题设置', 'manage_options', 'lei-notes', 'lei_notes_settings_page' ); } );
function lei_notes_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_POST['lei_notes_save'] ) ) {
        check_admin_referer( 'lei_notes_settings' );
        set_theme_mod( 'lei_notes_options', lei_notes_sanitize_options( wp_unslash( $_POST['lei_options'] ?? array() ) ) );
        echo '<div class="notice notice-success is-dismissible"><p>主题设置已保存。</p></div>';
    }
    $labels = array( 'logo_animation' => '字标循环书写动画', 'sticky_header' => '页头随滚动固定', 'reading' => '文章朗读', 'code' => '代码高亮与复制', 'line_numbers' => '代码行号（不参与复制）', 'image_zoom' => '文章图片放大', 'back_to_top' => '侧边回到顶部', 'seo' => '基础页面描述、规范地址与文章结构化信息' );
    echo '<div class="wrap"><h1>Lei 主题设置</h1><p>版面、页头、页脚、导航、文章列表和全局样式请在 <a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">外观 → 编辑器</a> 中修改；这里设置主题功能的默认值。区块可在编辑器中增删、移动和单独设置。</p><form method="post">';
    wp_nonce_field( 'lei_notes_settings' );
    echo '<table class="form-table"><tr><th scope="row"><label for="lei-appearance">访客默认外观</label></th><td><select id="lei-appearance" name="lei_options[appearance]">';
    foreach ( array( 'auto' => '跟随系统', 'light' => '白天模式', 'dark' => '黑夜模式' ) as $value => $label ) echo '<option value="' . esc_attr( $value ) . '" ' . selected( lei_notes_setting( 'appearance' ), $value, false ) . '>' . esc_html( $label ) . '</option>';
    echo '</select><p class="description">访客手动选择的外观优先于此默认值。</p></td></tr>';
    foreach ( $labels as $key => $label ) echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="lei_options[' . esc_attr( $key ) . ']" value="1" ' . checked( lei_notes_setting( $key ), true, false ) . '> 启用</label></td></tr>';
    foreach ( array( 'github' => 'GitHub', 'x' => 'Twitter / X', 'instagram' => 'Instagram' ) as $key => $label ) echo '<tr><th scope="row"><label for="lei-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input type="url" id="lei-' . esc_attr( $key ) . '" class="regular-text" name="lei_options[' . esc_attr( $key ) . ']" value="' . esc_attr( lei_notes_setting( $key ) ) . '" placeholder="https://"><p class="description">留空时保留图标占位；也可在页脚链接区块中单独填写。</p></td></tr>';
    echo '</table><p>搜索引擎可见性：' . ( get_option( 'blog_public' ) ? '当前允许索引。' : '当前已请求搜索引擎不索引。' ) . ' <a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">前往阅读设置</a></p>';
    submit_button( '保存主题设置', 'primary', 'lei_notes_save' );
    echo '</form><hr><h2>安装、停用与删除</h2><p>主题不创建文章、页面、用户、自定义数据表或计划任务。停用后专属脚本和样式停止加载，文章和图片保留。删除主题请先启用其他主题，再在“外观 → 主题”中删除；主题的个性化设置由 WordPress 按主题保存。</p></div>';
}
