<?php
/**
 * Plugin Name: Job iFrame Generator (Auto-URL)
 * Description: Erzeugt einen iFrame-Code, um Joblisten von der aktuellen Seite auf anderen Webseiten einzubetten, mit anpassbarem Design.
 * Version: 1.3.1-smoketest
 * Author: Ihr Name
 * License: GPLv2 or later
 * Text Domain: job-iframe-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_shortcode( 'job_embed_configurator', 'jig_configurator_shortcode_handler' );

function jig_configurator_shortcode_handler() {
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_script( 'jig-configurator-script', plugin_dir_url( __FILE__ ) . 'jig-script.js', array( 'jquery', 'wp-color-picker' ), '1.3.1-smoketest', true );

    $current_page_url = '';
    if ( is_singular() ) {
        $current_page_url = get_permalink( get_the_ID() );
    } else {
        global $wp;
        $current_page_url = home_url( add_query_arg( array(), $wp->request ) );
    }
    $current_page_url = esc_url( $current_page_url );

    $localized_data = array(
        'ajax_url'         => admin_url( 'admin-ajax.php' ),
        'nonce'            => wp_create_nonce( 'jig_iframe_content_nonce' ),
        'current_page_url' => $current_page_url,
        // Minimal texts for smoke test
        'txt_error_url'    => __('Fehler: URL-Problem (Konsole prüfen).', 'job-iframe-generator'),
        'txt_js_error'     => __('JavaScript Fehler (Konsole prüfen).', 'job-iframe-generator'),
    );
    wp_localize_script( 'jig-configurator-script', 'jig_ajax', $localized_data );

    // Debug log for server side
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $debug_data = $localized_data;
        $debug_data['nonce'] = 'nonce_is_set_for_debug_log'; // Don't log actual nonce
        error_log('JIG SMOKETEST Debug: Localized jig_ajax data: ' . print_r($debug_data, true));
    }


    ob_start();
    ?>
    <div id="jig-configurator-wrap">
        <h2>Job iFrame Konfigurator (SMOKE TEST VERSION)</h2>
        <p>Jobs werden von dieser Seite geladen: <strong><?php echo esc_html($current_page_url); ?></strong></p>
        <?php if (empty($current_page_url) || !filter_var($current_page_url, FILTER_VALIDATE_URL)) : ?>
            <p style="color:red; border:1px solid red; padding:10px;"><strong>Fehler: Die aktuelle Seiten-URL konnte nicht zuverlässig ermittelt werden!</strong></p>
        <?php endif; ?>

        <table class="form-table">
             <tbody>
                <tr>
                    <th scope="row"><label for="jig-bg-color-content">Hintergrundfarbe (Inhalt):</label></th>
                    <td><input type="text" id="jig-bg-color-content" name="jig-bg-color-content" class="jig-color-picker" value="#FFFFFF"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="jig-text-color-content">Text/Linkfarbe (Inhalt):</label></th>
                    <td><input type="text" id="jig-text-color-content" name="jig-text-color-content" class="jig-color-picker" value="#333333"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="jig-link-hover-color-content">Link Hover-Farbe (Inhalt):</label></th>
                    <td><input type="text" id="jig-link-hover-color-content" name="jig-link-hover-color-content" class="jig-color-picker" value="#0073aa"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="jig-iframe-border-color">Rahmenfarbe (iFrame):</label></th>
                    <td><input type="text" id="jig-iframe-border-color" name="jig-iframe-border-color" class="jig-color-picker" value="#CCCCCC"></td>
                </tr>
            </tbody>
        </table>

        <p><button type="button" id="jig-generate-button" class="button button-primary">Vorschau & Code generieren (SMOKE TEST)</button></p>
        <div id="jig-message-area" style="margin-top:10px; padding:10px; border:1px solid transparent; display:none;">SMOKE TEST Message Area</div>

        <h3>Vorschau:</h3>
        <div id="jig-preview-area" style="border: 1px solid #ddd; padding: 10px; min-height:150px; background:#f9f9f9; overflow:auto;">
            <p><em>Vorschau (SMOKE TEST)</em></p>
            <iframe id="jig-preview-iframe" style="width:100%; height:200px; border:1px solid #CCCCCC; background-color: white;"></iframe>
        </div>
        <h3>Generierter iFrame-Code:</h3>
        <textarea id="jig-generated-code" rows="6" style="width:100%; font-family: monospace;" readonly placeholder="Code-Ausgabe (SMOKE TEST)"></textarea>
    </div>
    <style> /* Styles bleiben für Grundlayout */
        #jig-configurator-wrap .form-table th { padding: 10px; text-align: left; }
        #jig-configurator-wrap .form-table td { padding: 10px; }
        #jig-message-area.error { border-color: red; color: red; background-color: #ffe0e0; }
        #jig-message-area.success { border-color: green; color: green; background-color: #e0ffe0; }
    </style>
    <?php
    return ob_get_clean();
}

// --- AJAX Handler (bleibt erstmal bestehen, wird vom Smoke-Test-JS aber nicht voll genutzt) ---
add_action( 'wp_ajax_nopriv_jig_get_iframe_content', 'jig_ajax_iframe_content_handler' );
add_action( 'wp_ajax_jig_get_iframe_content', 'jig_ajax_iframe_content_handler' );

function jig_ajax_iframe_content_handler() {
    if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['nonce'] ), 'jig_iframe_content_nonce' ) ) {
        jig_output_iframe_error_html('Sicherheitsüberprüfung fehlgeschlagen.', '#FFFFFF', '#000000'); wp_die();
    }
    $source_url = isset( $_GET['source_url'] ) ? esc_url_raw( $_GET['source_url'] ) : '';
    $bg_color = isset( $_GET['bg_color'] ) ? sanitize_hex_color( $_GET['bg_color'] ) : '#FFFFFF';
    $text_color = isset( $_GET['text_color'] ) ? sanitize_hex_color( $_GET['text_color'] ) : '#333333';
    $hover_color = isset( $_GET['hover_color'] ) ? sanitize_hex_color( $_GET['hover_color'] ) : '#0073aa';

    if ( empty( $source_url ) ) { jig_output_iframe_error_html('<p>Fehler: URL nicht übermittelt.</p>', $bg_color, $text_color); wp_die(); }
    if ( ! filter_var( $source_url, FILTER_VALIDATE_URL ) ) { jig_output_iframe_error_html('<p>Fehler: Ungültige URL.</p>', $bg_color, $text_color); wp_die(); }

    $jobs = jig_scrape_jobs_from_url( $source_url );
    $error_message = '';
    if ($jobs === false) { $error_message = '<p>Fehler beim Laden/Verarbeiten der Jobs von Seite: '. esc_html($source_url) .'. (Evtl. JS-abhängig oder Struktur unbekannt).</p>'; }
    elseif (empty($jobs)) { $error_message = '<p>Keine Jobs gefunden auf Seite: '. esc_html($source_url) .'.</p>'; }

    header( 'Content-Type: text/html; charset=utf-8' );
    ?>
    <!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Jobliste</title>
    <style>body{background-color:<?php echo esc_attr($bg_color);?>;color:<?php echo esc_attr($text_color);?>;font-family:sans-serif;margin:0;padding:15px;}ul{list-style:none;padding:0;margin:0;}li{padding:10px 0;border-bottom:1px solid <?php echo esc_attr(jig_adjust_brightness($text_color,120));?>;}li:last-child{border-bottom:none;}a{color:<?php echo esc_attr($text_color);?>;text-decoration:none;font-weight:bold;}a:hover,a:focus{color:<?php echo esc_attr($hover_color);?>;text-decoration:underline;}.jig-error-message{padding:10px;background-color:<?php echo esc_attr(jig_adjust_brightness($bg_color,-10));?>;border:1px solid <?php echo esc_attr(jig_adjust_brightness($text_color,100));?>;}</style>
    </head><body>
    <?php if(!empty($error_message)):?><div class="jig-error-message"><?php echo wp_kses_post($error_message);?></div>
    <?php elseif(!empty($jobs)):?><ul><?php foreach($jobs as $job):?><li><a href="<?php echo esc_url($job['url']);?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($job['title']);?></a></li><?php endforeach;?></ul>
    <?php endif;?>
    </body></html>
    <?php
    wp_die();
}

function jig_output_iframe_error_html($message, $bg_color, $text_color) { /* bleibt wie gehabt */
    header( 'Content-Type: text/html; charset=utf-8' );
    ?>
    <!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Fehler</title>
    <style>body{background-color:<?php echo esc_attr($bg_color);?>;color:<?php echo esc_attr($text_color);?>;padding:20px;font-family:sans-serif;}.jig-error-message{padding:10px;background-color:<?php echo esc_attr(jig_adjust_brightness($bg_color,-10));?>;border:1px solid <?php echo esc_attr(jig_adjust_brightness($text_color,100));?>;}</style>
    </head><body><div class="jig-error-message"><?php echo wp_kses_post( $message ); ?></div></body></html>
    <?php
}
function jig_adjust_brightness($hex, $steps) { /* bleibt wie gehabt */
    $hexInput = ltrim($hex, '#');
    if (strlen($hexInput) == 3) { $hexInput = $hexInput[0] . $hexInput[0] . $hexInput[1] . $hexInput[1] . $hexInput[2] . $hexInput[2]; }
    if (strlen($hexInput) != 6) { return '#CCCCCC';  }
    $r = hexdec(substr($hexInput,0,2)); $g = hexdec(substr($hexInput,2,2)); $b = hexdec(substr($hexInput,4,2));
    $r = max(0,min(255,$r + $steps)); $g = max(0,min(255,$g + $steps)); $b = max(0,min(255,$b + $steps));
    return '#'.str_pad(dechex($r),2,'0', STR_PAD_LEFT).str_pad(dechex($g),2,'0', STR_PAD_LEFT).str_pad(dechex($b),2,'0', STR_PAD_LEFT);
}

// --- Scraping Funktion (bleibt erstmal die Version 1.3, wird vom Smoke-Test-JS aber nicht voll genutzt) ---
function jig_scrape_jobs_from_url( $target_url ) {
    // HIER MUSS DIE VOLLSTÄNDIGE SCRAPING FUNKTION AUS VERSION 1.3 STEHEN!
    // (Aus Kürze hier nicht erneut komplett abgedruckt)
    // Beispielanfang:
    $jobs = array();
    if ( ! filter_var( $target_url, FILTER_VALIDATE_URL ) ) { error_log( 'JIG Plugin: Ungültige URL in Scrape-Funktion: ' . $target_url ); return false; }
    $response = wp_remote_get( $target_url, array( 'timeout' => 20, 'user-agent' => 'WordPress Job Scraper Plugin/1.3.1-smoketest (+' . home_url() . ")" ) );
    if ( is_wp_error( $response ) ) { error_log( 'JIG Plugin: Fehler beim Abrufen der URL ' . $target_url . ': ' . $response->get_error_message() ); return false; }
    $html_body = wp_remote_retrieve_body( $response );
    $http_code = wp_remote_retrieve_response_code( $response );
    if ( $http_code !== 200 ) { error_log( 'JIG Plugin: Ungültiger HTTP Status Code ' . $http_code . ' von URL ' . $target_url ); }
    if ( empty( $html_body ) ) { error_log( 'JIG Plugin: Leere Antwort von URL ' . $target_url ); return false; }
    $libxml_previous_state = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if ( ! @$dom->loadHTML( mb_convert_encoding( $html_body, 'HTML-ENTITIES', 'UTF-8' ) ) ) { error_log( 'JIG Plugin: Konnte HTML nicht laden: ' . $target_url ); libxml_clear_errors(); libxml_use_internal_errors($libxml_previous_state); return false; }
    libxml_clear_errors(); libxml_use_internal_errors($libxml_previous_state);
    $xpath = new DOMXPath( $dom );
    $primary_xpath = "//article[contains(@class, 'job_listing')]//div[contains(@class, 'job-list-content')]//h2[contains(@class, 'job-title')]/a";
    $link_queries = [ $primary_xpath, /* ... andere fallbacks ... */ ];
    $job_link_elements = null;
    foreach($link_queries as $idx => $query) {
        $current_elements = $xpath->query( $query );
        if ($current_elements !== false && $current_elements->length > 0) { $job_link_elements = $current_elements; error_log("JIG SMOKETEST: XPath #{$idx} found {$job_link_elements->length} elements."); break; }
    }
    if (is_null($job_link_elements) || $job_link_elements->length === 0) { error_log( 'JIG SMOKETEST: Keine Job-Links gefunden für: ' . $target_url ); return []; }
    foreach ( $job_link_elements as $link_element ) {
        $job_url = $link_element->getAttribute( 'href' );
        $job_title = trim($link_element->nodeValue); 
        if (empty($job_title)) $job_title = 'Jobangebot';
        // URL Auflösung (vereinfacht für Übersicht, Originalcode ist komplexer)
        if ( !empty($job_url) && filter_var( $job_url, FILTER_VALIDATE_URL ) === false ) { // Relative URL?
            $url_parts_target = parse_url($target_url);
            if (isset($url_parts_target['scheme']) && isset($url_parts_target['host'])) {
                $base_job_url = $url_parts_target['scheme'] . '://' . $url_parts_target['host'];
                if ($job_url[0] === '/') { $job_url = $base_job_url . $job_url; }
                else { $job_url = rtrim($base_job_url, '/') . '/' . ltrim($job_url, '/');} // Vereinfacht
            }
        }
        if (filter_var($job_url, FILTER_VALIDATE_URL)) { $jobs[] = array( 'title' => $job_title, 'url' => $job_url ); }
    }
    error_log( 'JIG SMOKETEST: Extracted ' . count($jobs) . ' jobs from ' . $target_url );
    return $jobs;
}

/**
 * Enthält den stark vereinfachten JavaScript-Code für den Rauchtest.
 * Es wird DRINGEND EMPFOHLEN, die jig-script.js Datei MANUELL im Plugin-Verzeichnis
 * mit dem unten stehenden Code zu erstellen und zu pflegen.
 */
function jig_create_admin_js_file_if_not_exists() {
    $js_file_path = plugin_dir_path( __FILE__ ) . 'jig-script.js';
    // Nur erstellen, wenn nicht vorhanden. Manuelle Aktualisierung wird stark empfohlen.
    if ( ! file_exists( $js_file_path ) ) {
        $js_content = <<<'EOD'
jQuery(document).ready(function($) {
    // SMOKE TEST JAVASCRIPT (Version 1.3.1-smoketest)
    // BITTE PRÜFEN SIE DIE BROWSER-ENTWICKLERKONSOLE (F12) AUF AUSGABEN VON "JIG SMOKE TEST"!
    console.log('JIG SMOKE TEST SCRIPT LOADED (Version 1.3.1-smoketest). If you see this, the file is running.');
    var smokeTestErrorOccurred = false;

    function showSmokeTestUserMessage(message, type) {
        var $messageArea = $('#jig-message-area'); // Dieses Element muss im HTML des Shortcodes existieren
        if ($messageArea.length) {
            $messageArea.text(message).removeClass('error success loading').addClass(type).show();
        } else {
            // Fallback, falls Message-Area nicht da ist (sollte nicht sein)
            console.warn("JIG SMOKE TEST: Message area #jig-message-area not found, using alert.");
            if (type === 'error' && !smokeTestErrorOccurred) {
                 alert("JIG SMOKE TEST Info: " + message + "\n(Message area missing. Please also check browser console with F12)");
                 smokeTestErrorOccurred = true; // Verhindert weitere Alerts
            } else if (type !== 'error') {
                 console.log("JIG SMOKE TEST Info: " + message);
            }
        }
    }

    // 1. jQuery-Prüfung
    if (typeof jQuery === 'undefined') {
        console.error('JIG SMOKE TEST: jQuery is not loaded! Plugin will not work.');
        showSmokeTestUserMessage('Kritischer Fehler: jQuery nicht geladen. Plugin funktioniert nicht.', 'error');
        return; // Stoppt weitere Ausführung dieses Skripts
    }
    console.log('JIG SMOKE TEST: jQuery is available.');

    // 2. jig_ajax Objekt Prüfung (von wp_localize_script)
    if (typeof jig_ajax === 'undefined' || typeof jig_ajax.ajax_url === 'undefined' || typeof jig_ajax.nonce === 'undefined' || typeof jig_ajax.current_page_url === 'undefined') {
        console.error('JIG SMOKE TEST Error: jig_ajax object or one of its key properties (ajax_url, nonce, current_page_url) is not defined. Check wp_localize_script in PHP.');
        showSmokeTestUserMessage('Plugin-Fehler: Wichtige Konfigurationsdaten (jig_ajax) fehlen. Generierung nicht möglich. (Konsole prüfen!)', 'error');
        // Nicht unbedingt return, aber die Hauptfunktion wird fehlschlagen.
    } else {
        console.log('JIG SMOKE TEST: jig_ajax Config seems OK:', jig_ajax);
    }
    
    // 3. ColorPicker Initialisierung (vereinfacht, nur Log)
    try {
        if ($.isFunction($.fn.wpColorPicker)) {
            // Für den Smoke-Test nicht initialisieren, um Fehlerquelle auszuschließen, nur prüfen ob vorhanden.
            // $('.jig-color-picker').wpColorPicker({...}); 
            console.log('JIG SMOKE TEST: wpColorPicker function IS available.');
        } else {
            console.warn('JIG SMOKE TEST Warning: wpColorPicker is NOT a function. Color inputs would be standard text fields.');
        }
    } catch(e) {
        console.error('JIG SMOKE TEST Error checking/initializing color pickers:', e);
    }

    // 4. Button und Klick-Handler
    var $generateButton = $('#jig-generate-button');
    if ($generateButton.length) {
        console.log('JIG SMOKE TEST: Generate button #jig-generate-button FOUND in DOM.');
        $generateButton.on('click', function() {
            console.log('JIG SMOKE TEST: Generate button CLICKED!');
            smokeTestErrorOccurred = false; // Reset für neue Aktion
            showSmokeTestUserMessage('SMOKE TEST: Button Klick erkannt!', 'success');

            // Minimal-Logik für den Klick im Smoke Test
            var $textarea = $('#jig-generated-code');
            if ($textarea.length) {
                var testContent = "SMOKE TEST: Button wurde geklickt am " + new Date().toLocaleTimeString();
                if (typeof jig_ajax !== 'undefined' && jig_ajax.current_page_url) {
                    testContent += "\nAktuelle URL (vom JS gesehen): " + jig_ajax.current_page_url;
                } else {
                    testContent += "\nFEHLER: jig_ajax.current_page_url nicht verfügbar im JS!";
                }
                $textarea.val(testContent);
                console.log("JIG SMOKE TEST: Textarea updated.");
            } else {
                console.error("JIG SMOKE TEST: Textarea #jig-generated-code not found.");
            }
            // Im Smoke-Test wird die volle updateIframeCodeAndPreview nicht aufgerufen,
            // um Komplexität zu reduzieren und nur den Klick-Handler zu testen.
        });
        console.log('JIG SMOKE TEST: Click handler for generate button ATTACHED.');
    } else {
        console.error('JIG SMOKE TEST Error: Generate button #jig-generate-button NOT FOUND in DOM.');
        showSmokeTestUserMessage('Plugin-Fehler: "Generieren"-Button nicht im HTML gefunden. (Konsole prüfen!)', 'error');
    }

    // Initial-Meldung für den Nutzer
    showSmokeTestUserMessage('SMOKE TEST Modus aktiv. Bitte Konsole (F12) für Details prüfen.', 'success');
    console.log('JIG SMOKE TEST: End of jQuery(document).ready(). Script initialization complete.');

}); // Ende jQuery(document).ready()
EOD;
        // @ unterdrückt Fehler bei file_put_contents, falls keine Schreibrechte. Manuelle Erstellung ist besser.
        if (@file_put_contents( $js_file_path, $js_content ) === false) {
            if (defined('WP_DEBUG') && WP_DEBUG && WP_DEBUG_LOG) {
                error_log('JIG SMOKETEST Plugin: Konnte jig-script.js NICHT automatisch erstellen. Bitte MANUELL im Plugin-Ordner anlegen/aktualisieren: ' . $js_file_path);
            }
        } else {
             if (defined('WP_DEBUG') && WP_DEBUG && WP_DEBUG_LOG) {
                error_log('JIG SMOKETEST Plugin: jig-script.js wurde (neu) geschrieben nach: ' . $js_file_path);
            }
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG && WP_DEBUG_LOG) {
            error_log('JIG SMOKETEST Plugin: jig-script.js existiert bereits. Manuelle Aktualisierung mit SMOKE TEST Code empfohlen: ' . $js_file_path);
        }
    }
}
register_activation_hook( __FILE__, 'jig_create_admin_js_file_if_not_exists' );
add_action( 'init', 'jig_create_admin_js_file_if_not_exists' ); // Versucht, beim Laden zu erstellen, falls nicht vorhanden

?>
