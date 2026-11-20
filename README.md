# fxTeX maths for Moodle (filter_fxtex)

A Moodle filter that typesets the LaTeX in Moodle's content with
[fxTeX Pro](https://fxeditor.codecogs.com/docs/0-fxtex.php), CodeCogs' maths engine, in place of
MathJax. Equations become MathML, typeset in the reader's browser in a few milliseconds, with
their spoken form for screen readers. With fxTeX Pro, Moodle can also show what MathJax cannot:

- chemical structures and reaction schemes, written with `\chemfig` and `\schemestart`
- pictures drawn with LaTeX's `picture` environment
- the physics package's `\dv`, `\pdv`, `\qty` and the rest

as well as chemical formulae (`\ce`, `\pu`) and the rest of LaTeX's maths.

It reads what Moodle's content already uses: `\(...\)` inline, `\[...\]` and `$$...$$` on their own
line, and optionally `$...$`. So existing content written for MathJax needs no changes, and nor does
content from the [CodeCogs Equation Editor](https://fxplugins.codecogs.com/docs/moodle.html)
(`tiny_eqneditor`), whose MathML equations this filter also styles.

In Moodle the plugin is called **fxTeX maths**; its component is `filter_fxtex`, and it installs to
`filter/fxtex`. It needs Moodle 4.1 or later and has been tested on Moodle 4.5 LTS.

## Switching from MathJax

1. Install the plugin: copy this folder to `filter/fxtex` (or install it as a ZIP under
   *Site administration > Plugins > Install plugins*) and visit *Site administration > Notifications*.
   Its JavaScript is already built.
2. Under *Site administration > Plugins > Filters > Manage filters*, turn **fxTeX maths** on, and
   **MathJax** off. Turn off **TeX notation** too if it is on; this filter can read its
   `[tex]...[/tex]` instead (see the settings below).
3. Optionally, paste a licence key into the filter's settings (or the equation editor's).

Filters can also be turned on and off for a single course or activity, under its *Filters* page.

## fxTeX Pro or fxTeX

With an fxTeX Pro licence key the filter uses **fxTeX Pro**, which has the chemistry, picture and
physics packages above. Without one it uses the free **fxTeX**: the same MathML for everything
else, including `\ce`, but chemical structures, pictures and the physics package's commands stay
as their LaTeX.

Get a 30-day trial key, or buy one, for your site's domain from
https://fxeditor.codecogs.com/purchase. The key given to the CodeCogs Equation Editor is used if
the filter has none of its own. If the key does not open fxTeX Pro on the page's domain, or has
expired, the filter uses fxTeX instead and says why in the browser's console.

## Settings

| Setting | Default | |
|---|---|---|
| Licence key | | Your fxTeX Pro key (`fx_pk_...`). Empty: the equation editor's key, or fxTeX if there is none. |
| Single dollar signs | Off | Also read `$...$` as inline maths. Off because a single `$` is common in ordinary text - a price in a forum post. |
| TeX filter compatibility | Off | Also read the old TeX notation filter's `[tex]...[/tex]` and `<tex>...</tex>` as inline maths. |
| fxTeX URL | `https://fxtex.codecogs.com` | Where fxTeX and fxTeX Pro are loaded from: CodeCogs' service, or your own copy of it. |

## How it works

On the server, the filter finds each equation in a piece of content and puts it in a `nolink` span,
so that other filters (glossary links, activity names) leave its LaTeX alone. It looks only at
text: never at tags or attribute values, and not inside `<math>`, `<code>`, `<pre>`, `<script>`,
`<style>`, `<textarea>` or `<svg>`.

In the browser, `amd/src/loader.js` loads fxTeX Pro or fxTeX from the fxTeX URL, only on a page
that has maths on it, and typesets the page. Content added later - a quiz question, a forum reply -
is typeset as Moodle announces it. Text editors are never typeset, as their content is still being
written. Equation images are left as they are.

The Moodle app does not run this filter's JavaScript, so the app shows the LaTeX as it is written.

## Privacy

The plugin stores no personal data. The reader's browser loads fxTeX or fxTeX Pro from
fxtex.codecogs.com (or the fxTeX URL), sending the page's address as the referrer, which fxTeX Pro
uses to check that the licence key is valid for the site.

## Building

The compiled JavaScript in `amd/build` is made from `amd/src` with Moodle's own grunt, inside a
Moodle checkout (Node 22):

```
npx grunt amd --root=filter/fxtex
```

## Licence

GNU GPL v3 or later - see LICENSE. fxTeX and fxTeX Pro themselves are not part of this plugin;
they are loaded from CodeCogs' service and are covered by their own licences.
