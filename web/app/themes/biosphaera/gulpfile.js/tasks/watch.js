var gulp               = require('gulp')
  , browserSync        = require("browser-sync").get('My server')
  , runSequence        = require('run-sequence')
  , configBrowsersync  = require('../gulpconfig').browsersync
  , configWatch        = require('../gulpconfig').watch
;

gulp.task('watch',['build'], function(callback) {
  browserSync.init(configBrowsersync);
  runSequence(
              'watch-font-css',
              'watch-styles',
              'watch-scripts',
              ['watch-fonts', 'watch-images'],
              'watch-config',
              callback
  );  
});

gulp.task('watch-config', function() {
  gulp.watch(configWatch.configFiles, ['build']);
});