<?php
/**
 * Optional password gate for responses handled by this WordPress theme.
 * Direct files and responses served before WordPress by a host/CDN cache are outside its scope.
 */
defined( 'ABSPATH' ) || exit;

function bsw_site_privacy_settings() {
    $stored = get_option( 'bsw_site_privacy', array() );
    $stored = is_array( $stored ) ? $stored : array();
    return array(
        'enabled'       => ! empty( $stored['enabled'] ),
        'password_hash' => isset( $stored['password_hash'] ) && is_string( $stored['password_hash'] ) ? $stored['password_hash'] : '',
        'revision'      => isset( $stored['revision'] ) && is_string( $stored['revision'] ) ? $stored['revision'] : '',
    );
}

function bsw_site_privacy_enabled() {
    return bsw_site_privacy_settings()['enabled'];
}

// Run when functions.php loads, before template processing. Earlier host/page caches still need bypassing.
function bsw_site_privacy_cache_mode() {
    if ( ! bsw_site_privacy_enabled() ) { return; }
    foreach ( array( 'DONOTCACHEPAGE', 'DONOTCACHEOBJECT', 'DONOTCACHEDB' ) as $constant ) {
        if ( ! defined( $constant ) ) { define( $constant, true ); }
    }
    // Core can exit with a feed 304 before template_redirect; protected responses must be re-authorized.
    unset( $_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
}
bsw_site_privacy_cache_mode();

function bsw_site_privacy_headers() {
    if ( ! bsw_site_privacy_enabled() || headers_sent() ) { return; }
    nocache_headers();
    header_remove( 'ETag' );
    header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
    header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
    header( 'Vary: Cookie', false );
}

function bsw_site_privacy_wp_headers( $headers ) {
    if ( ! bsw_site_privacy_enabled() ) { return $headers; }
    $headers = array_merge( $headers, wp_get_nocache_headers() );
    unset( $headers['ETag'] );
    $headers['Cache-Control'] = 'private, no-store, no-cache, must-revalidate, max-age=0';
    $headers['X-Robots-Tag'] = 'noindex, nofollow, noarchive';
    $vary = isset( $headers['Vary'] ) ? $headers['Vary'] : '';
    $headers['Vary'] = preg_match( '/(?:^|,)\s*cookie\s*(?:,|$)/i', $vary ) ? $vary : trim( $vary . ', Cookie', ', ' );
    return $headers;
}
add_filter( 'wp_headers', 'bsw_site_privacy_wp_headers', 999 );
add_action( 'login_init', 'bsw_site_privacy_headers' );

// Yoast serves XML during pre_get_posts, before core sends its usual wp_headers.
// Its filter uses complete header lines as keys, unlike WordPress's name/value map.
function bsw_site_privacy_sitemap_headers( $headers ) {
    if ( ! bsw_site_privacy_enabled() ) { return $headers; }
    foreach ( array_keys( $headers ) as $header ) {
        if ( preg_match( '/^(?:Cache-Control|Expires|Pragma|ETag|Last-Modified|X-Robots-Tag|Vary)\s*:/i', $header ) ) { unset( $headers[ $header ] ); }
    }
    $headers['Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'] = '';
    $headers['Expires: Wed, 11 Jan 1984 05:00:00 GMT'] = '';
    $headers['Pragma: no-cache'] = '';
    $headers['X-Robots-Tag: noindex, nofollow, noarchive'] = '';
    $headers['Vary: Cookie'] = '';
    return $headers;
}
add_filter( 'wpseo_sitemap_http_headers', 'bsw_site_privacy_sitemap_headers', 999 );

function bsw_site_privacy_robots( $robots ) {
    if ( bsw_site_privacy_enabled() ) {
        unset( $robots['index'], $robots['follow'], $robots['archive'] );
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        $robots['noarchive'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'bsw_site_privacy_robots', 999 );

function bsw_site_privacy_cookie_name() {
    return 'bsw_site_access_' . substr( hash( 'sha256', get_current_blog_id() . '|' . get_option( 'siteurl' ) ), 0, 12 );
}

function bsw_site_privacy_cookie_signature( $expires, $settings ) {
    $message = implode( '|', array( 'bsw-site-privacy-v1', get_current_blog_id(), get_option( 'siteurl' ), $expires, $settings['revision'], $settings['password_hash'] ) );
    return hash_hmac( 'sha256', $message, wp_salt( 'auth' ) );
}

function bsw_site_privacy_cookie_valid( $settings = null ) {
    $settings = null === $settings ? bsw_site_privacy_settings() : $settings;
    if ( '' === $settings['password_hash'] || '' === $settings['revision'] ) { return false; }
    $name = bsw_site_privacy_cookie_name();
    $value = isset( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : '';
    if ( strlen( $value ) > 100 || ! preg_match( '/^1\.([0-9]{1,12})\.([a-f0-9]{64})$/D', $value, $matches ) ) { return false; }
    $expires = (int) $matches[1];
    if ( $expires <= time() || $expires > time() + 7 * DAY_IN_SECONDS + MINUTE_IN_SECONDS ) { return false; }
    return hash_equals( bsw_site_privacy_cookie_signature( $expires, $settings ), $matches[2] );
}

function bsw_site_privacy_has_access() {
    return current_user_can( 'manage_options' ) || bsw_site_privacy_cookie_valid();
}

function bsw_site_privacy_editor_access() {
    // REST runs this after WordPress has checked its own nonce/application-password authentication.
    return is_user_logged_in() && current_user_can( 'edit_posts' );
}

function bsw_site_privacy_issue_cookie( $settings ) {
    if ( headers_sent() ) { return false; }
    $expires = time() + 7 * DAY_IN_SECONDS;
    $value = '1.' . $expires . '.' . bsw_site_privacy_cookie_signature( $expires, $settings );
    return setcookie( bsw_site_privacy_cookie_name(), $value, array(
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => is_ssl() || 'https' === wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ),
        'httponly' => true,
        'samesite' => 'Lax',
    ) );
}

/** Accept only local, root-relative paths; never a user-supplied host or scheme. */
function bsw_site_privacy_return_path( $value ) {
    $fallback = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $fallback = is_string( $fallback ) && '' !== $fallback ? $fallback : '/';
    if ( ! is_string( $value ) || '' === $value || strlen( $value ) > 2048 ) { return $fallback; }
    // Sanitization can collapse '/|/host' or '/%0a/host' into '//host'; validate its final output.
    $value = wp_sanitize_redirect( $value );
    if ( '' === $value || '/' !== $value[0] || 0 === strpos( $value, '//' ) ) { return $fallback; }
    if ( preg_match( '/[\x00-\x20\x7f\\\\]/', $value ) ) { return $fallback; }
    $decoded_start = rawurldecode( substr( $value, 0, 64 ) );
    if ( 0 === strpos( $decoded_start, '//' ) || false !== strpos( $decoded_start, '\\' ) ) { return $fallback; }
    $parts = wp_parse_url( $value );
    if ( false === $parts || isset( $parts['host'] ) || isset( $parts['scheme'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) { return $fallback; }
    $path = isset( $parts['path'] ) ? $parts['path'] : '/';
    return $path . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
}

/**
 * Five failed passwords per fixed 15-minute window, in 256 fixed IP-HMAC buckets.
 * This bounds transient keys, stores no IP addresses, and never trusts forwarded headers.
 * Bucket collisions and non-atomic transient writes make this a practical throttle, not a firewall.
 */
function bsw_site_privacy_attempt_state( $settings ) {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    $ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
    $bucket = hexdec( substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 2 ) );
    $key = 'bsw_privacy_attempt_' . $bucket;
    $state = get_transient( $key );
    $now = time();
    if ( ! is_array( $state ) || ! isset( $state['until'], $state['count'], $state['revision'] ) || $state['revision'] !== $settings['revision'] || $state['until'] <= $now || $state['until'] > $now + 15 * MINUTE_IN_SECONDS ) {
        $state = array( 'until' => $now + 15 * MINUTE_IN_SECONDS, 'count' => 0, 'revision' => $settings['revision'] );
    }
    $state['count'] = min( 5, max( 0, (int) $state['count'] ) );
    return array( 'key' => $key, 'state' => $state );
}

function bsw_site_privacy_retry_after( $settings ) {
    $attempt = bsw_site_privacy_attempt_state( $settings );
    return $attempt['state']['count'] >= 5 ? max( 1, $attempt['state']['until'] - time() ) : 0;
}

function bsw_site_privacy_failed_attempt( $settings ) {
    $attempt = bsw_site_privacy_attempt_state( $settings );
    $attempt['state']['count'] = min( 5, $attempt['state']['count'] + 1 );
    $remaining = max( 1, $attempt['state']['until'] - time() );
    set_transient( $attempt['key'], $attempt['state'], $remaining );
    return $attempt['state']['count'] >= 5 ? $remaining : 0;
}

function bsw_site_privacy_render_gate( $return_path, $error = '', $status = 401, $retry_after = 0 ) {
    $settings = bsw_site_privacy_settings();
    bsw_site_privacy_headers();
    status_header( $status );
    header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
    if ( $retry_after ) { header( 'Retry-After: ' . (int) $retry_after ); }
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'HEAD' === $_SERVER['REQUEST_METHOD'] ) { exit; }
    ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?php echo esc_html( bsw_t( 'Private website' ) . ' · ' . get_bloginfo( 'name' ) ); ?></title>
    <link rel="icon" type="image/png" href="<?php echo esc_url( get_theme_file_uri( '/assets/favicon.png' ) ); ?>">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f3ed;color:#24382d;font:17px/1.55 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh;display:grid;place-items:center;padding:24px}main{width:100%;max-width:470px;padding:clamp(24px,6vw,44px);background:#fff;border:1px solid #dddcd1;border-radius:16px;box-shadow:0 18px 50px #24382d0c}.brand{display:block;max-width:290px;width:100%;height:auto;margin:0 auto 32px}h1{font-size:clamp(25px,6vw,32px);line-height:1.18;margin:0 0 14px}p{margin:0 0 22px}label{display:block;font-weight:650;margin-bottom:8px}input[type=password]{width:100%;font:inherit;padding:12px 14px;border:1px solid #788777;border-radius:6px;color:inherit;background:#fff}button{width:100%;margin:18px 0 0;padding:13px 18px;font:inherit;font-weight:650;border:0;border-radius:6px;background:#36583c;color:#fff;cursor:pointer}button:hover{background:#24452c}input:focus-visible,button:focus-visible,a:focus-visible{outline:3px solid #ad732f;outline-offset:3px}.error{padding:12px 14px;border-left:3px solid #a03b31;background:#fff2ee;color:#6d251f}.note{font-size:14px;color:#5d685d;margin:18px 0 0}.admin{display:block;margin-top:24px;font-size:14px;color:#36583c}
    </style>
</head>
<body>
<main>
    <img class="brand" src="<?php echo esc_url( get_theme_file_uri( '/assets/logo.png' ) ); ?>" width="700" height="160" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
    <h1><?php echo esc_html( bsw_t( 'This website is private.' ) ); ?></h1>
    <p><?php echo esc_html( bsw_t( 'Enter the website password to continue.' ) ); ?></p>
    <?php if ( $error ) : ?><p class="error" id="privacy-error" role="alert"><?php echo esc_html( $error ); ?></p><?php endif; ?>
    <form method="post" action="<?php echo esc_url( $return_path ); ?>">
        <?php wp_nonce_field( 'bsw_site_privacy_unlock_' . $settings['revision'], 'bsw_privacy_nonce', false ); ?>
        <input type="hidden" name="bsw_privacy_action" value="unlock">
        <input type="hidden" name="bsw_privacy_return" value="<?php echo esc_attr( $return_path ); ?>">
        <label for="bsw-privacy-password"><?php echo esc_html( bsw_t( 'Website password' ) ); ?></label>
        <input id="bsw-privacy-password" type="password" name="bsw_privacy_password" value="" maxlength="4096" autocomplete="current-password" required<?php echo $error ? ' aria-describedby="privacy-error" aria-invalid="true"' : ''; ?>>
        <button type="submit"><?php echo esc_html( bsw_t( 'Continue to website' ) ); ?></button>
    </form>
    <p class="note"><?php echo esc_html( bsw_t( 'This browser will stay unlocked for 7 days, unless the password or protection setting changes.' ) ); ?></p>
    <a class="admin" href="<?php echo esc_url( wp_login_url( admin_url() ) ); ?>"><?php echo esc_html( bsw_t( 'Administrator sign in' ) ); ?></a>
</main>
</body>
</html>
    <?php
    exit;
}

function bsw_site_privacy_frontend() {
    if ( ! bsw_site_privacy_enabled() ) { return; }
    bsw_site_privacy_headers();
    if ( is_robots() ) {
        bsw_site_privacy_headers();
        status_header( 200 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'HEAD' !== $_SERVER['REQUEST_METHOD'] ) { echo "User-agent: *\nDisallow: /\n"; }
        exit;
    }
    if ( bsw_site_privacy_has_access() ) { return; }
    $settings = bsw_site_privacy_settings();
    $return_path = bsw_site_privacy_return_path( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
    $is_unlock = isset( $_SERVER['REQUEST_METHOD'], $_POST['bsw_privacy_action'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && is_string( $_POST['bsw_privacy_action'] ) && 'unlock' === $_POST['bsw_privacy_action'];
    if ( $is_unlock ) {
        $return_path = bsw_site_privacy_return_path( isset( $_POST['bsw_privacy_return'] ) && is_string( $_POST['bsw_privacy_return'] ) ? wp_unslash( $_POST['bsw_privacy_return'] ) : '' );
        $nonce = isset( $_POST['bsw_privacy_nonce'] ) && is_string( $_POST['bsw_privacy_nonce'] ) ? wp_unslash( $_POST['bsw_privacy_nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'bsw_site_privacy_unlock_' . $settings['revision'] ) ) {
            bsw_site_privacy_render_gate( $return_path, bsw_t( 'Your session has expired. Refresh this page and try again.' ), 403 );
        }
        $retry_after = bsw_site_privacy_retry_after( $settings );
        if ( $retry_after ) { bsw_site_privacy_render_gate( $return_path, bsw_t( 'Too many attempts. Please try again later.' ), 429, $retry_after ); }
        $password = isset( $_POST['bsw_privacy_password'] ) && is_string( $_POST['bsw_privacy_password'] ) ? wp_unslash( $_POST['bsw_privacy_password'] ) : '';
        // WordPress's native hasher trims edge whitespace; normalize identically when checking.
        $password = strlen( $password ) <= 4096 ? trim( $password ) : '';
        if ( '' !== $password && '' !== $settings['password_hash'] && wp_check_password( $password, $settings['password_hash'] ) ) {
            if ( ! bsw_site_privacy_issue_cookie( $settings ) ) {
                bsw_site_privacy_render_gate( $return_path, bsw_t( 'Access could not be saved in this browser. Please try again.' ), 500 );
            }
            $attempt = bsw_site_privacy_attempt_state( $settings );
            delete_transient( $attempt['key'] );
            wp_safe_redirect( $return_path, 303, 'Beautiful Stone Walls' );
            exit;
        }
        $retry_after = bsw_site_privacy_failed_attempt( $settings );
        bsw_site_privacy_render_gate( $return_path, $retry_after ? bsw_t( 'Too many attempts. Please try again later.' ) : bsw_t( 'That password is not correct. Please try again.' ), $retry_after ? 429 : 401, $retry_after );
    }
    bsw_site_privacy_render_gate( $return_path );
}
add_action( 'template_redirect', 'bsw_site_privacy_frontend', -1000 );

// Gate the main query before providers that output and exit early (for example Yoast sitemaps).
// parse_query has already established query flags and Polylang's URL language at this point.
function bsw_site_privacy_main_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) { bsw_site_privacy_frontend(); }
}
add_action( 'pre_get_posts', 'bsw_site_privacy_main_query', -1000 );

function bsw_site_privacy_rest_authentication( $result ) {
    if ( ! bsw_site_privacy_enabled() || is_wp_error( $result ) ) { return $result; }
    // Core can return true for an anonymous REST request; always check actual access independently.
    if ( bsw_site_privacy_has_access() || bsw_site_privacy_editor_access() ) { return $result; }
    return new WP_Error( 'bsw_site_private', bsw_t( 'This website is private. Enter its password on the website to continue.' ), array( 'status' => 401 ) );
}
add_filter( 'rest_authentication_errors', 'bsw_site_privacy_rest_authentication', 200 );

function bsw_site_privacy_rest_headers( $response ) {
    if ( bsw_site_privacy_enabled() ) {
        $response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
        $response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
        $response->header( 'Vary', 'Cookie', false );
    }
    return $response;
}
add_filter( 'rest_post_dispatch', 'bsw_site_privacy_rest_headers', 999 );
add_filter( 'rest_send_nocache_headers', static function ( $send ) { return bsw_site_privacy_enabled() || $send; }, 999 );

function bsw_site_privacy_admin_endpoints() {
    if ( ! bsw_site_privacy_enabled() ) { return; }
    bsw_site_privacy_headers();
    $ajax = wp_doing_ajax();
    $admin_post = isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'];
    if ( ! $ajax && ! $admin_post ) { return; }
    if ( bsw_site_privacy_has_access() || bsw_site_privacy_editor_access() ) { return; }
    $action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : '';
    // Native password recovery uses only this content-free, public AJAX action.
    if ( $ajax && 'generate-password' === $action ) { return; }
    $message = bsw_t( 'This website is private. Enter its password on the website to continue.' );
    if ( $ajax ) { wp_send_json_error( array( 'message' => $message ), 403 ); }
    wp_die( esc_html( $message ), esc_html( bsw_t( 'Private website' ) ), array( 'response' => 403 ) );
}
add_action( 'admin_init', 'bsw_site_privacy_admin_endpoints', 0 );

function bsw_site_privacy_legacy_endpoints() {
    if ( ! bsw_site_privacy_enabled() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || 'cli' === PHP_SAPI ) { return; }
    $xmlrpc = defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
    $legacy = isset( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'wp-comments-post.php', 'wp-trackback.php', 'wp-links-opml.php' ), true );
    if ( ! $xmlrpc && ! $legacy ) { return; }
    bsw_site_privacy_headers();
    if ( bsw_site_privacy_has_access() || bsw_site_privacy_editor_access() ) { return; }
    status_header( 403 );
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo esc_html( bsw_t( 'This website is private. Enter its password on the website to continue.' ) );
    exit;
}
add_action( 'init', 'bsw_site_privacy_legacy_endpoints', 0 );

function bsw_site_privacy_admin_menu() {
    add_theme_page( bsw_t( 'Site Privacy' ), bsw_t( 'Site Privacy' ), 'manage_options', 'bsw-site-privacy', 'bsw_site_privacy_settings_page' );
}
add_action( 'admin_menu', 'bsw_site_privacy_admin_menu' );

function bsw_site_privacy_settings_redirect( $status ) {
    wp_safe_redirect( add_query_arg( array( 'page' => 'bsw-site-privacy', 'privacy-status' => $status ), admin_url( 'themes.php' ) ), 303, 'Beautiful Stone Walls' );
    exit;
}

function bsw_site_privacy_save_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html( bsw_t( 'You do not have permission to change site privacy settings.' ) ), '', array( 'response' => 403 ) );
    }
    $nonce = isset( $_POST['bsw_privacy_settings_nonce'] ) && is_string( $_POST['bsw_privacy_settings_nonce'] ) ? wp_unslash( $_POST['bsw_privacy_settings_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'bsw_save_site_privacy' ) ) {
        wp_die( esc_html( bsw_t( 'Your session has expired. Refresh this page and try again.' ) ), '', array( 'response' => 403 ) );
    }
    $previous = bsw_site_privacy_settings();
    $next = $previous;
    $next['enabled'] = isset( $_POST['bsw_privacy_enabled'] ) && is_string( $_POST['bsw_privacy_enabled'] ) && '1' === $_POST['bsw_privacy_enabled'];
    if ( ! isset( $_POST['bsw_privacy_new_password'] ) || ! is_string( $_POST['bsw_privacy_new_password'] ) ) { bsw_site_privacy_settings_redirect( 'invalid-password' ); }
    $password = wp_unslash( $_POST['bsw_privacy_new_password'] );
    if ( strlen( $password ) > 4096 ) { bsw_site_privacy_settings_redirect( 'invalid-password' ); }
    $password = trim( $password );
    if ( '' !== $password ) { $next['password_hash'] = wp_hash_password( $password ); }
    if ( $next['enabled'] && ( '' === $next['password_hash'] || '*' === $next['password_hash'] ) ) { bsw_site_privacy_settings_redirect( 'password-required' ); }
    if ( '' !== $password || $next['enabled'] !== $previous['enabled'] || '' === $next['revision'] ) { $next['revision'] = wp_generate_password( 32, false, false ); }
    // A dedicated, non-autoload option survives theme ZIP updates and contains no plaintext password.
    $saved = update_option( 'bsw_site_privacy', $next, false );
    if ( ! $saved && get_option( 'bsw_site_privacy' ) !== $next ) { bsw_site_privacy_settings_redirect( 'save-failed' ); }
    bsw_site_privacy_settings_redirect( 'saved' );
}
add_action( 'admin_post_bsw_save_site_privacy', 'bsw_site_privacy_save_settings' );

function bsw_site_privacy_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $settings = bsw_site_privacy_settings();
    $status = isset( $_GET['privacy-status'] ) && is_string( $_GET['privacy-status'] ) ? sanitize_key( wp_unslash( $_GET['privacy-status'] ) ) : '';
    $notices = array(
        'saved'             => array( 'success', bsw_t( 'Site privacy settings saved.' ) ),
        'password-required' => array( 'error', bsw_t( 'Set a password before enabling protection.' ) ),
        'invalid-password'  => array( 'error', bsw_t( 'That password is not valid or is too long. Please enter a shorter password.' ) ),
        'save-failed'       => array( 'error', bsw_t( 'Your privacy settings could not be saved. Please try again.' ) ),
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( bsw_t( 'Site Privacy' ) ); ?></h1>
        <p><?php echo esc_html( bsw_t( 'Choose whether visitors need a password to view this website.' ) ); ?></p>
        <?php if ( isset( $notices[ $status ] ) ) : ?>
            <div class="notice notice-<?php echo esc_attr( $notices[ $status ][0] ); ?> is-dismissible"><p><?php echo esc_html( $notices[ $status ][1] ); ?></p></div>
        <?php endif; ?>
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="bsw_save_site_privacy">
            <?php wp_nonce_field( 'bsw_save_site_privacy', 'bsw_privacy_settings_nonce', false ); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><?php echo esc_html( bsw_t( 'Password protection' ) ); ?></th><td>
                    <label><input type="checkbox" name="bsw_privacy_enabled" value="1" <?php checked( $settings['enabled'] ); ?>> <?php echo esc_html( bsw_t( 'Require a password to view this website' ) ); ?></label>
                    <p class="description"><?php echo esc_html( bsw_t( 'Protection is off by default. Administrators keep access, and signed-in editors can continue using WordPress editing tools.' ) ); ?></p>
                </td></tr>
                <tr><th scope="row"><label for="bsw-privacy-new-password"><?php echo esc_html( bsw_t( 'Website password' ) ); ?></label></th><td>
                    <input class="regular-text" id="bsw-privacy-new-password" name="bsw_privacy_new_password" type="password" value="" maxlength="4096" autocomplete="new-password">
                    <p class="description"><?php echo esc_html( '' !== $settings['password_hash'] ? bsw_t( 'A password is set. Leave this field blank to keep it.' ) : bsw_t( 'Set a password before enabling protection.' ) ); ?></p>
                    <p class="description"><?php echo esc_html( bsw_t( 'Spaces at the beginning and end are ignored. The saved password is never displayed.' ) ); ?></p>
                </td></tr>
            </table>
            <p><?php echo esc_html( bsw_t( 'Changing the password or protection setting signs out visitors who previously unlocked the website.' ) ); ?></p>
            <?php submit_button( bsw_t( 'Save privacy settings' ) ); ?>
        </form>
        <h2><?php echo esc_html( bsw_t( 'Before enabling protection' ) ); ?></h2>
        <p><?php echo esc_html( bsw_t( 'Disable or bypass full-page caching for this website, then clear any existing hosting, cache-plugin and CDN caches.' ) ); ?></p>
        <p><?php echo esc_html( bsw_t( 'This theme protects pages handled by WordPress. Direct media and file URLs, and copies already served by a hosting or CDN cache, may remain accessible. Use hosting-level protection if those files must also be private.' ) ); ?></p>
        <p><?php echo esc_html( bsw_t( 'These settings survive theme updates. Protection works only while this theme is active.' ) ); ?></p>
    </div>
    <?php
}
