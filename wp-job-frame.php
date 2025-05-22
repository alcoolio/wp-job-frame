<?php
/**
 * Plugin Name: Job iFrame Generator (Auto-URL)
 * Description: Erzeugt einen iFrame-Code, um Joblisten von der aktuellen Seite auf anderen Webseiten einzubetten, mit anpassbarem Design.
 * Version: 1.3.2
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
    wp_enqueue_script( 'jig-configurator-script', plugin_dir_url( __FILE__ ) . 'jig-script.js', array( 'jquery', 'wp-color-picker' ), '1.3.2', true );

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
        <h2>Job iFrame Konfigurator</h2>
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

        <p><button type="button" id="jig-generate-button" class="button button-primary">Vorschau & Code generieren</button></p>
        <div id="jig-message-area" style="margin-top:10px; padding:10px; border:1px solid transparent; display:none;"></div>

        <h3>Vorschau:</h3>
        <div id="jig-preview-area" style="border: 1px solid #ddd; padding: 10px; min-height:150px; background:#f9f9f9; overflow:auto;">
            <p><em>Vorschau:</em></p>
            <iframe id="jig-preview-iframe" style="width:100%; height:200px; border:1px solid #CCCCCC; background-color: white;"></iframe>
        </div>
        <h3>Generierter iFrame-Code:</h3>
        <textarea id="jig-generated-code" rows="6" style="width:100%; font-family: monospace;" readonly placeholder="Der generierte iFrame-Code erscheint hier..."></textarea>
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

?>
