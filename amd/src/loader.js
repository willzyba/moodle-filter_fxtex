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

/**
 * Typesets the maths on a page with fxTeX Pro, or fxTeX without a licence key.
 *
 * configure() is called on every page the filter is on; nothing is loaded until typeset() is, which
 * the filter calls only for content that has maths in it, or until content added later (a quiz
 * question, a forum reply) turns out to have some.
 *
 * @module      filter_fxtex/loader
 * @copyright   2026 CodeCogs, Zyba Ltd. <https://www.codecogs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {eventTypes} from 'core_filters/events';
import Log from 'core/log';

// fxTeX Pro's optional packages, all on: chemical formulae (\ce), chemical structures (\chemfig),
// the physics package (\dv and the rest) and the picture environment. The free fxTeX has only the first.
const PACKAGES = ['mhchem', 'chemfig', 'physics', 'picture'];

// Never typeset: fxTeX's own list, and the places on a Moodle page where maths is being written
// rather than read - the text editors, whose content is still the author's LaTeX.
const IGNORE = 'script, style, textarea, pre, code, noscript, math, .no-cc-render, ' +
    '[contenteditable="true"], .tox, .editor_atto_content_wrap';

let config = null;
let loading = null;
let pending = null;

/**
 * Loads a script once.
 *
 * @param {string} url The script.
 * @returns {Promise} Resolves once it has run.
 */
const loadScript = (url) => new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = url;
    script.async = true;
    script.addEventListener('load', () => resolve());
    script.addEventListener('error', () => reject(new Error(`could not load ${url}`)));
    document.head.appendChild(script);
});

/**
 * The free fxTeX.
 *
 * @returns {Promise<object>} Resolves to window.fxTeX.
 */
const loadFree = () => (window.fxTeX ? Promise.resolve() : loadScript(`${config.server}/fxtex.min.js`))
    .then(() => window.fxTeX);

/**
 * fxTeX Pro, opened with the site's key and with its packages ready. If the key does not open it -
 * the wrong domain, or expired - the free fxTeX instead: Pro would show the reason in place of every
 * equation, and the free one still typesets everything but the packages it lacks.
 *
 * @returns {Promise<object>} Resolves to window.fxTeXPro, or window.fxTeX.
 */
const loadPro = async() => {
    if (!window.fxTeXPro) {
        await loadScript(`${config.server}/${encodeURIComponent(config.licence)}/fxtexpro.min.js`);
    }
    const fx = window.fxTeXPro;
    await fx.ready;
    const result = fx.unlock(config.licence);
    // A key fxTeX Server does not know, or one that has run out, gets a stand-in build that says
    // 'ok' but is expired, and shows "Licence expired" in place of each equation.
    const info = typeof fx.info === 'function' ? fx.info() : {};
    if (result !== 'ok' || info.expired) {
        Log.warn(`fxTeX Pro: the licence key did not open it here (${result !== 'ok' ? result : 'expired'}); ` +
            'using fxTeX instead');
        return loadFree();
    }
    if (typeof fx.usePackages === 'function') {
        await fx.usePackages(PACKAGES);
    }
    return fx;
};

/**
 * fxTeX Pro or fxTeX, loaded once.
 *
 * @returns {Promise<object>} Resolves to the one loaded.
 */
const load = () => {
    if (!loading) {
        loading = (config.licence ? loadPro() : loadFree()).catch((err) => {
            Log.warn(`fxTeX Pro: ${err && err.message || err}; using fxTeX instead`);
            return loadFree();
        });
    }
    return loading;
};

/**
 * Typesets the maths under some elements.
 *
 * @param {Element[]} roots The elements.
 * @returns {Promise}
 */
const renderIn = (roots) => load().then((fx) => {
    const delimiters = [...fx.DEFAULT_DELIMITERS, ...fx.LATEX_BRACKET_DELIMITERS]
        .filter((d) => config.singledollar || d.left !== '$');
    // Already-typeset maths is <math>, which IGNORE leaves alone, so typesetting twice is harmless.
    // Equation images are left as they are: they show already, and the free fxTeX could not draw a
    // chemical structure or a picture from one.
    return Promise.all(roots.map((root) => fx.render({root, delimiters, ignoreSelector: IGNORE, images: false})));
}).catch((err) => {
    Log.error(`fxTeX: ${err && err.message || err}`);
});

/**
 * Whether some content could hold maths: one of the delimiters, or MathML (which needs the
 * stylesheet fxTeX brings).
 *
 * @param {Node} node The content.
 * @returns {boolean}
 */
const hasMaths = (node) => {
    if (node.nodeType !== Node.ELEMENT_NODE && node.nodeType !== Node.DOCUMENT_FRAGMENT_NODE) {
        return false;
    }
    const text = node.textContent || '';
    return text.includes('$$') || text.includes('\\(') || text.includes('\\[') ||
        (config.singledollar && text.includes('$')) || !!(node.querySelector && node.querySelector('math'));
};

/**
 * Sets the loader up, once per page.
 *
 * @param {object} params {server, licence, singledollar}, from the filter's settings.
 */
export const configure = (params) => {
    if (config) {
        return;
    }
    config = params;
    // Content a page adds after loading - a quiz question, a forum reply, a block - announces itself
    // with this event, after Moodle's filters have been through it.
    document.addEventListener(eventTypes.filterContentUpdated, (event) => {
        const nodes = Array.from(event.detail.nodes || []).filter(hasMaths);
        if (nodes.length) {
            renderIn(nodes);
        }
    });
};

/**
 * Typesets the page. Called for each piece of content with maths in it, so the calls made while the
 * page loads are gathered into one.
 */
export const typeset = () => {
    if (!config || pending) {
        return;
    }
    pending = new Promise((resolve) => {
        const go = () => {
            pending = null;
            renderIn([document.body]).then(resolve);
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', go);
        } else {
            setTimeout(go, 0);
        }
    });
};
