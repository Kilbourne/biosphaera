var gulp              = require('gulp')
  , $                 = require('gulp-load-plugins')({ camelize: true })
  , config            = require('../gulpconfig').scripts
  , argv             = require('minimist')(process.argv.slice(2))
  , enabled = {
      // Enable static asset revisioning when `--production`
      rev: argv.production,
      // Disable source maps when `--production`
      maps: !argv.production,
      // Fail styles task on error when `--production`
      failStyleTask: argv.production,
      // Fail due to JSHint warnings only when `--production`
      failJSHint: false,
      // Strip debug statments from javascript when `--production`
      stripJSDebug: argv.production
    }
  , writeToManifest   = require('./manifest').writeToManifest
  , manifest = require('../gulpconfig').manifest.manifest
  , lazypipe          = require('lazypipe')
  , merge             = require('merge-stream')
  , browserSync      = require("browser-sync").get('My server')
;

var jsTasks = function(filename) {
  return lazypipe()
    .pipe(function() {
      return $.if(enabled.maps, $.sourcemaps.init());
    })
    .pipe($.concat, filename)
    .pipe(function() {
      return $.if(enabled.rev, $.uglify(config.uglify));
    })
    .pipe(function() {
      return $.if(enabled.rev, $.rev());
    })
    .pipe(function() {
      return $.if(enabled.maps, $.sourcemaps.write('.',config.sourceMap));
    })();
};

gulp.task('scripts', ['jshint'], function() {
  var merged = merge();
  manifest.forEachDependency('js', function(dep) {
    merged.add(
      gulp.src(dep.globs, {base: config.base})
        .pipe(jsTasks(dep.name))
    );
  });
  return merged
    .pipe(writeToManifest(config.manifestDir));
});

gulp.task('jshint', function() {
  return gulp.src(config.otherHint.concat(config.project))
    .pipe($.jshint())
    .pipe($.jshint.reporter('jshint-stylish'))
    .pipe($.if(enabled.failJSHint, $.jshint.reporter('fail')));
});

gulp.task('watch-scripts', function() {
  gulp.watch([config.src + '/**/*'], ['jshint', 'scripts']);
});
