<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_fxtex;

// Moodle 4.5 gave filters a namespaced base class; 4.1 to 4.4 have only moodle_text_filter.
if (class_exists(\core_filters\text_filter::class)) {
    class_alias(\core_filters\text_filter::class, base_text_filter::class);
} else {
    class_alias(\moodle_text_filter::class, base_text_filter::class);
}

/**
 * Typesets the LaTeX in Moodle's content with fxTeX or fxTeX Pro, in the reader's browser.
 *
 * The filter itself only finds the maths and keeps other filters out of it: each equation is put in
 * a nolink span, so a glossary or activity-name link cannot be made inside its LaTeX. The
 * typesetting is done in the browser by amd/src/loader.js, which loads fxTeX Pro (with a licence key)
 * or fxTeX (without one) only on a page that has maths on it.
 *
 * @package     filter_fxtex
 * @copyright   2026 CodeCogs, Zyba Ltd. <https://www.codecogs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends base_text_filter {
    /** @var string The service fxTeX and fxTeX Pro load from, unless the site sets another. */
    const DEFAULT_SERVER = 'https://fxtex.codecogs.com';

    /** @var string[] Elements whose content is never maths to typeset. */
    const SKIP = ['math', 'script', 'style', 'pre', 'code', 'textarea', 'svg'];

    /**
     * Sets the page up to typeset: the loader's settings, once per page. The loader adds nothing to
     * the page until some content has maths in it (see filter()).
     *
     * @param \moodle_page $page The page.
     * @param \context $context The context.
     */
    public function setup($page, $context) {
        if (!$page->requires->should_create_one_time_item_now('filter_fxtex-loader')) {
            return;
        }
        $page->requires->js_call_amd('filter_fxtex/loader', 'configure', [self::settings()]);
    }

    /**
     * The loader's settings.
     *
     * @return array server, licence and singledollar.
     */
    public static function settings(): array {
        $config = get_config('filter_fxtex');
        $server = trim($config->server ?? '') ?: self::DEFAULT_SERVER;
        // One key for the site: the equation editor's, unless this filter is given its own.
        $licence = trim($config->licence ?? '');
        if ($licence === '') {
            $licence = trim((string) get_config('tiny_eqneditor', 'licence'));
        }
        return [
            'server' => rtrim($server, '/'),
            'licence' => $licence,
            'singledollar' => !empty($config->singledollar),
        ];
    }

    /**
     * Finds the maths in some content and keeps other filters out of it.
     *
     * @param string $text The content.
     * @param array $options Filter options.
     * @return string The content, each equation in a nolink span.
     */
    public function filter($text, array $options = []) {
        global $PAGE;

        if (!is_string($text) || $text === '') {
            return $text;
        }
        $config = get_config('filter_fxtex');

        if (!empty($config->texcompat)) {
            // The old TeX notation filter's [tex] ... [/tex] and <tex> ... </tex>, as inline maths.
            $text = preg_replace('~<(/?) *tex( [^>]*)?>~iu', '[\1tex]', $text);
            $text = str_ireplace(['[tex]', '[/tex]'], ['\\(', '\\)'], $text);
        }

        // Nothing that could be maths: by far the most common case, left as fast as it can be.
        $hasmathml = stripos($text, '<math') !== false;
        if (!$hasmathml && strpos($text, '$') === false && strpos($text, '\\(') === false
                && strpos($text, '\\[') === false) {
            return $text;
        }

        $found = false;
        $text = self::wrap_maths($text, !empty($config->singledollar), $found);

        // MathML already in the content (as the equation editor inserts it) needs fxTeX's stylesheet,
        // which comes with the script, and its chemical structures and pictures are fxTeX Pro's own.
        if ($found || $hasmathml) {
            $PAGE->requires->js_call_amd('filter_fxtex/loader', 'typeset');
        }
        return $text;
    }

    /**
     * Puts each equation in the text of some HTML - $$...$$, \[...\], \(...\), and $...$ if the site
     * has asked for it - in a nolink span. Only text is looked at, never tags or attribute values, and
     * not the content of SKIP's elements.
     *
     * @param string $html The HTML.
     * @param bool $singledollar Whether $...$ is maths.
     * @param bool $found Set to whether there was any.
     * @return string The HTML.
     */
    public static function wrap_maths(string $html, bool $singledollar, bool &$found = false): string {
        $pattern = '~\$\$.+?\$\$|\\\\\[.+?\\\\\]|\\\\\(.+?\\\\\)';
        if ($singledollar) {
            // Not \$, which is a dollar sign; not empty; not across a line break.
            $pattern .= '|(?<![\\\\$])\$(?!\$)(?:\\\\.|[^$\\\\\n])+?\$';
        }
        $pattern .= '~su';

        $parts = preg_split('~(<[^>]*>)~u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        $skipping = [];
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                // A tag: note where a skipped element starts and ends.
                if (preg_match('~^<(/?)([a-z][a-z0-9-]*)~i', $part, $tag)) {
                    $name = strtolower($tag[2]);
                    if (in_array($name, self::SKIP)) {
                        if ($tag[1] === '') {
                            if (substr($part, -2) !== '/>') {
                                $skipping[] = $name;
                            }
                        } else if (end($skipping) === $name) {
                            array_pop($skipping);
                        }
                    }
                }
                continue;
            }
            if ($skipping || $part === '') {
                continue;
            }
            $parts[$i] = preg_replace_callback($pattern, function ($match) use (&$found) {
                $found = true;
                return '<span class="nolink">' . $match[0] . '</span>';
            }, $part);
        }
        return implode('', $parts);
    }
}
