<?php
/**
 * Plugin Name: TSV Wartungsmodus & Redirect-Tester
 * Description: Schützt die Seite, bietet eine Info-Seite für Gäste und erlaubt Redirect-Tests.
 * Version: 1.2
 * Author: Hersteller.io
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Einstellungsseite im Dashboard erstellen
add_action('admin_menu', function() {
    add_options_page('Wartungsmodus Settings', 'Wartungsmodus', 'manage_options', 'ts-maintenance', 'wartungsmodus_settings_page');
});

function wartungsmodus_settings_page() {
    ?>
    <div class="wrap">
        <h1>Wartungsmodus & Redirect-Tester</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ts_maintenance_group');
            do_settings_sections('ts-maintenance');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('ts_maintenance_group', 'ts_redirect_test_mode');
    add_settings_section('ts_settings_main', 'Einstellungen', null, 'ts-maintenance');
    add_settings_field('ts_test_mode', 'Redirect-Test-Modus aktiv?', 'wartungsmodus_field_test_mode_render', 'ts-maintenance', 'ts_settings_main');
});

function wartungsmodus_field_test_mode_render() {
    $val = get_option('ts_redirect_test_mode');
    echo '<input type="checkbox" name="ts_redirect_test_mode" value="1" ' . checked(1, $val, false) . ' />';
    echo '<p class="description">Aktiv: URLs sind aufrufbar (für Redirect-Tests). Inaktiv: Alles leitet auf / weiter.</p>';
}

// 2. Die Logik für den Zugriffsschutz
add_action('template_redirect', function() {
    // Wenn Admin, Login oder eingeloggt: Nichts tun
    if ( is_admin() || strpos($_SERVER['PHP_SELF'], 'wp-login.php') !== false || is_user_logged_in() ) {
        return;
    }

    $is_test_mode = get_option('ts_redirect_test_mode');

    // Falls NICHT im Test-Modus: Alles auf / leiten
    if ( ! $is_test_mode && $_SERVER['REQUEST_URI'] !== '/' ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    // Bots & Google blockieren
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');

    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Hier entsteht etwas Neues</title>
        <style>
            body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f0f2f5; color: #1c1e21; text-align: center; }
            .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); max-width: 450px; }
            a { color: #007bff; text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Hier entsteht unsere neue Website</h1>
            <p>Bis es so weit ist, findest du uns unter:</p>
            <p><a href="https://tierheim-duesseldorf.de">tierheim-duesseldorf.de</a></p>
            <div style="margin-top:30px; font-size:0.8rem; opacity:0.5;">
                <a href="<?php echo wp_login_url(); ?>">Anmelden</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
});
