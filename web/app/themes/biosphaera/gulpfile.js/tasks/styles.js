var gulp = require('gulp'),
    $ = require('gulp-load-plugins')({ camelize: true, DEBUG: true }),
    config = require('../gulpconfig').styles,
    argv = require('minimist')(process.argv.slice(2)),
    enabled = {
        // Enable static asset revisioning when `--production`
        rev: argv.production,
        // Disable source maps when `--production`
        maps: !argv.production,
        // Fail styles task on error when `--production`
        failStyleTask: argv.production,
        // Fail due to JSHint warnings only when `--production`
        failJSHint: argv.production,
        // Strip debug statments from javascript when `--production`
        stripJSDebug: argv.production
    },
    writeToManifest = require('./manifest').writeToManifest,
    manifest = require('../gulpconfig').manifest.manifest,
    lazypipe = require('lazypipe'),
    merge = require('merge-stream'),
    browserSync = require("browser-sync").get('My server'),
    wiredep = require('wiredep').stream;


var cssTasks = function(filename) {
    return lazypipe()
        .pipe(function() {
            return $.if(!enabled.failStyleTask, $.plumber());
        })
        .pipe(function() {
            return $.if(enabled.maps, $.sourcemaps.init());
        })
        .pipe(function() {
            return $.if('*.scss', $.sass(config.sass));
        })
        .pipe($.concatUtil, filename)
        .pipe($.autoprefixer, { browsers: config.browsers })
        .pipe($.minifyCss, config.minify)
        .pipe(function() {
            return $.if(enabled.rev, $.rev());
        })
        .pipe(function() {
            return $.if(enabled.maps, $.sourcemaps.write('.', config.sourceMap));
        })();
};

gulp.task('styles', ['bowerCss'], function() {
    var merged = merge();
    manifest.forEachDependency('css', function(dep) {
        var cssTasksInstance = cssTasks(dep.name);
        if (!enabled.failStyleTask) {
            cssTasksInstance.on('error', function(err) {
                console.error(err.message);
                this.emit('end');
            });
        }
        merged.add(gulp.src(dep.globs, { base: config.globsBase })
            .pipe(cssTasksInstance));
    });
    return merged
        .pipe(writeToManifest(config.manifestDir));
});

gulp.task('bowerCss', function() {
    console.log($);
    return gulp.src(config.project)
        .pipe(wiredep())
        .pipe($.changed(config.src, { hasChanged: $.changed.compareSha1Digest }))
        .pipe(gulp.dest(config.src));
});

gulp.task('watch-styles', function() {
    gulp.watch([config.src + '/**/*'], ['styles']);
});
