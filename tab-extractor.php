<?php
/*
Plugin Name: ACF Tab Extractor & Cleaner for Events
Description: Extracts tab headings/content (after first content appears) from mec-events, saves to ACF, and removes all tab-related lines from post content. Go to Tools > ACF Tab Extractor.
Version: 1.5
Author: Your Name
*/

add_action('admin_menu', function () {
    add_management_page(
        'ACF Tab Extractor',
        'ACF Tab Extractor',
        'manage_options',
        'acf-tab-extractor',
        'acf_tab_extractor_page'
    );
});

function acf_tab_extractor_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission.');
    }

    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    echo '<div class="wrap"><h1>ACF Tab Extractor & Cleaner</h1>';
    if (isset($_POST['acf_tab_extract_run'])) {
        echo "<div style='background:#111;color:#fff;padding:14px;'>[Tab extraction started!]</div>";

        $args = [
            'post_type'      => 'mec-events',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);
        $migrated = 0;

        foreach ($query->posts as $post) {
            try {
                $migrated++;
                $content = $post->post_content;
                $lines = preg_split('/\r\n|\r|\n/', $content);

                $tabs = [];
                $current_heading = '';
                $current_content = '';
                $started = false;
                $tab_list_start = -1; // start of the initial tab heading list
                $tab_start_line = -1; // line where actual tab headings+content start

                // Find start of tab list and start of tabs with content
                for ($i = 0; $i < count($lines); $i++) {
                    $line = $lines[$i];
                    $trimmed = trim($line);
                    $is_heading = ($trimmed !== '' && !preg_match('/^<.*>$/', $trimmed));
                    if ($trimmed === '') continue;

                    if ($tab_list_start === -1 && $is_heading) {
                        // first possible heading: start of tab name list
                        $tab_list_start = $i;
                    }

                    if (!$started && $is_heading) {
                        // Look ahead to next non-empty line
                        $j = $i + 1;
                        while ($j < count($lines) && trim($lines[$j]) === '') $j++;
                        $next_trim = ($j < count($lines)) ? trim($lines[$j]) : '';
                        $next_is_heading = ($next_trim !== '' && !preg_match('/^<.*>$/', $next_trim));
                        if ($j < count($lines) && !$next_is_heading) {
                            // Found the start of real tabs!
                            $started = true;
                            $tab_start_line = $i;
                            $current_heading = $trimmed;
                            $i = $j - 1; // Start from next line
                            continue;
                        }
                    }
                    if ($started) break;
                }

                // -- Tab extraction, after we've found where real tabs+content start --
                if ($tab_start_line > -1) {
                    for ($i = $tab_start_line; $i < count($lines); $i++) {
                        $line = $lines[$i];
                        $trimmed = trim($line);
                        $is_heading = ($trimmed !== '' && !preg_match('/^<.*>$/', $trimmed));
                        if ($trimmed === '') continue;
                        if ($is_heading) {
                            if ($current_heading && trim(strip_tags($current_content))) {
                                $tabs[] = [
                                    'tab_heading' => $current_heading,
                                    'tab_content' => trim($current_content),
                                ];
                                $current_content = '';
                            }
                            $current_heading = $trimmed;
                        } else {
                            $current_content .= $line . "\n";
                        }
                    }
                    // Save last tab
                    if ($current_heading && trim(strip_tags($current_content))) {
                        $tabs[] = [
                            'tab_heading' => $current_heading,
                            'tab_content' => trim($current_content),
                        ];
                    }
                }

                // Save to ACF and update post content
                if (!empty($tabs)) {
                    update_field('tabs', $tabs, $post->ID);

                    // Remove everything from the tab list onwards
                    if ($tab_list_start > -1) {
                        $clean_content = implode("\n", array_slice($lines, 0, $tab_list_start));
                        wp_update_post([
                            'ID' => $post->ID,
                            'post_content' => trim($clean_content),
                        ]);
                        echo "<pre style='background:#222;color:#fff;padding:8px;'>Extracted " . count($tabs) . " tabs for post ID {$post->ID}: " . get_the_title($post->ID) . " (all tab lines removed from post)</pre>";
                    } else {
                        echo "<pre style='background:#222;color:#fff;padding:8px;'>Extracted " . count($tabs) . " tabs for post ID {$post->ID}: " . get_the_title($post->ID) . "</pre>";
                    }
                } else {
                    echo "<pre style='background:#440;color:#fff;padding:8px;'>No tabs found for post ID {$post->ID}: " . get_the_title($post->ID) . "</pre>";
                }

                @flush(); @ob_flush();
            } catch (Throwable $e) {
                echo "<pre style='background:#a00;color:#fff;padding:8px;'>Error for post ID {$post->ID}: " . $e->getMessage() . "</pre>";
            }
        }

        if ($migrated === 0) {
            echo "<div style='background:#a00;color:#fff;padding:14px;'>No mec-events posts found.</div>";
        }

        echo '<div style="background:#222;color:#fff;padding:20px;">Tab extraction and cleaning finished.<br>Check your ACF fields and post content for results.</div>';
    } else {
        // Show the extract button
        echo '<form method="post">';
        echo '<p>This tool will extract tab headings and their content from mec-events post content (skipping any initial tab name list), <b>remove all tab-related lines from post content</b>, and populate the ACF tabs repeater field.<br>';
        echo '<b>Only the intro (if any) will remain in the post content. It will NOT affect galleries.</b></p>';
        echo '<p><input type="submit" class="button button-primary" name="acf_tab_extract_run" value="Extract & Clean Tabs Now"></p>';
        echo '</form>';
    }
    echo '</div>';
}
?>
