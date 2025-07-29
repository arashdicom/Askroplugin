const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// Set public path
mix.setPublicPath('assets');

// Compile CSS
mix.postCss('assets/css/src/main.css', 'assets/css/style.css', [
    require('tailwindcss'),
    require('autoprefixer')
]);

mix.postCss('assets/css/src/admin.css', 'assets/css/admin.css', [
    require('tailwindcss'),
    require('autoprefixer')
]);

// Compile JavaScript
mix.js('assets/js/src/main.js', 'assets/js/main.js');
mix.js('assets/js/src/admin.js', 'assets/js/admin.js');

// Create vendor directories
mix.copyDirectory('node_modules/chart.js/dist', 'assets/js/vendor/chart');
mix.copyDirectory('node_modules/animejs/lib', 'assets/js/vendor/anime');
mix.copyDirectory('node_modules/@yaireo/tagify/dist', 'assets/js/vendor/tagify');
mix.copyDirectory('node_modules/toastr/build', 'assets/js/vendor/toastr');
mix.copyDirectory('node_modules/swiper', 'assets/js/vendor/swiper');
mix.copyDirectory('node_modules/cropperjs/dist', 'assets/js/vendor/cropper');

// Options
mix.options({
    processCssUrls: false,
    postCss: [
        require('tailwindcss'),
        require('autoprefixer')
    ]
});

// Versioning in production
if (mix.inProduction()) {
    mix.version();
}

// Source maps in development
if (!mix.inProduction()) {
    mix.sourceMaps();
}

