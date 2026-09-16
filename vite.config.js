import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

/*
 * No Tailwind here on purpose.
 *
 * The board's look is a direct port of the design canvas and the Nocturne
 * design system it is built on — plain CSS, its own tokens, its own component
 * classes. An earlier pass re-expressed that design in utility classes and the
 * screens drifted apart from one another; keeping one vocabulary means a change
 * made on the canvas can be carried across by hand rather than translated.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * IBM Plex Sans Arabic carries both scripts at matching weight and
             * colour, so the page does not change texture when the language
             * does — the reason the canvas picked it.
             *
             * Self-hosted through Bunny at build time rather than linked from
             * Google Fonts: one less third party on the critical path, no
             * cross-origin font request, and it keeps working behind a CSP.
             *
             * English is themed onto Proxima Nova, which is licensed and is NOT
             * loaded — it falls back to Arial exactly as the canvas does. Add an
             * Adobe Fonts kit to the layout head when a licence is in place.
             */
            fonts: [
                bunny('IBM Plex Sans Arabic', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
