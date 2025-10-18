/**
 * PostCSS Configuration
 * 
 * Processes CSS for the Laravel Live Chat React components
 * - postcss-import: Resolves @import statements
 * - autoprefixer: Adds vendor prefixes for browser compatibility
 * - cssnano: Minifies CSS for production
 */

module.exports = {
    plugins: {
        'postcss-import': {},
        autoprefixer: {
            // Support last 2 versions of modern browsers
            overrideBrowserslist: [
                'last 2 Chrome versions',
                'last 2 Firefox versions',
                'last 2 Safari versions',
                'last 2 Edge versions',
            ],
        },
        // Minify CSS in production
        ...(process.env.NODE_ENV === 'production' && {
            cssnano: {
                preset: [
                    'default',
                    {
                        // Preserve CSS custom properties (CSS variables)
                        cssDeclarationSorter: false,
                        discardComments: {
                            removeAll: true,
                        },
                    },
                ],
            },
        }),
    },
};
