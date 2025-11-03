<?php
/**
 * Plugin Name:       Code Dark Copy
 * Description:       Dark, elegant code blocks with a single, beautiful copy button. Removes all duplicates. Works with any theme.
 * Version:           1.0.0
 * Author:            Fedya Serafiev
 * Text Domain:       code-dark-copy
 * Domain Path:       /languages
 * License:           MIT
 */

if (!defined('ABSPATH')) exit;

function code_dark_copy_enqueue() {
    global $post;
    if (!is_a($post, 'WP_Post')) return;

    $content = $post->post_content;
    if (strpos($content, 'wp:code') === false && strpos($content, '<code') === false && strpos($content, '<pre') === false) return;

    wp_enqueue_script('highlight-js', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js', [], '11.9.0', true);
    wp_enqueue_style('highlight-css', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/monokai-sublime.min.css');

    wp_add_inline_style('highlight-css', '
        button[class*="copy"]:not(.cdc-btn),
        .copy-code-button:not(.cdc-btn),
        .wp-block-code button:not(.cdc-btn) {
            display: none !important;
        }

        pre.cdc-block {
            background: #1e1e1e !important; color: #f8f8f2 !important; padding: 1.6em !important; border-radius: 12px !important;
            position: relative !important; margin: 2rem 0 !important; font-family: "Fira Code", monospace !important;
            font-size: 15.5px !important; line-height: 1.6 !important; box-shadow: 0 6px 18px rgba(0,0,0,0.4) !important;
            overflow-x: auto !important; border: 1px solid #333 !important;
        }
        pre.cdc-block code { background: none !important; color: inherit !important; padding: 0 !important; }

        .cdc-btn {
            position: absolute !important; top: 12px !important; right: 12px !important; z-index: 9999 !important;
            padding: 8px 14px !important; background: #2d2d2d !important; color: #a6e22e !important; border: 1px solid #444 !important;
            border-radius: 6px !important; font-size: 12px !important; font-weight: 600 !important; cursor: pointer !important;
            transition: all 0.3s !important; display: flex !important; align-items: center !important; gap: 6px !important;
        }
        .cdc-btn:hover { background: #3d3d3d !important; transform: translateY(-1px) !important; }
        .cdc-btn.copied { background: #1e6b3a !important; }
        .cdc-btn::before { content: "Clipboard"; font-size: 15px !important; }
        .cdc-btn.copied::before { content: "Checkmark"; }

        @media (max-width: 768px) { 
            pre.cdc-block { padding: 1.8rem 1rem 1.4rem !important; font-size: 14px !important; }
            .cdc-btn { top: 8px !important; right: 8px !important; padding: 6px 10px !important; font-size: 11px !important; }
        }
    ');

    wp_add_inline_script('highlight-js', '
        document.addEventListener("DOMContentLoaded", function() {
            function removeOldButtons() {
                document.querySelectorAll("button").forEach(btn => {
                    if ((btn.textContent || "").trim() === "Копирай" && !btn.classList.contains("cdc-btn")) {
                        btn.remove();
                    }
                });
            }
            removeOldButtons();
            let iv = setInterval(removeOldButtons, 200);
            setTimeout(() => clearInterval(iv), 3000);

            function init() {
                document.querySelectorAll("pre").forEach(pre => {
                    if (pre.classList.contains("cdc-block")) return;
                    const code = pre.querySelector("code");
                    if (!code) return;
                    pre.className = "cdc-block";
                    pre.style.position = "relative";
                    hljs.highlightElement(code);

                    if (pre.querySelector(".cdc-btn")) return;

                    const btn = document.createElement("button");
                    btn.className = "cdc-btn";
                    btn.innerHTML = "Копирай";
                    btn.onclick = function() {
                        navigator.clipboard.writeText(code.textContent).then(() => {
                            this.innerHTML = "Копирано!";
                            this.classList.add("copied");
                            setTimeout(() => {
                                this.innerHTML = "Копирай";
                                this.classList.remove("copied");
                            }, 2000);
                        });
                    };
                    pre.appendChild(btn);
                });
            }
            init();
            new MutationObserver(() => setTimeout(init, 100)).observe(document.body, { childList: true, subtree: true });
        });
    ');
}
add_action('wp_enqueue_scripts', 'code_dark_copy_enqueue', 999);

add_filter('render_block', function($content, $block) {
    if (in_array($block['blockName'], ['core/code', 'core/html', 'core/preformatted'])) {
        $content = preg_replace('/<code/', '<code class="hljs"', $content);
    }
    return $content;
}, 10, 2);
