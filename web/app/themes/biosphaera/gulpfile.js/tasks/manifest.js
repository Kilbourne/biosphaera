var gulp        = require('gulp')
  , $           = require('gulp-load-plugins')({ camelize: true })
  , config      = require('../gulpconfig').manifest
  , lazypipe    = require('lazypipe')
  , browserSync = require("browser-sync").get('My server')
  ;



// ### Write to rev manifest
// If there are any revved files then write them to the rev manifest.
// See https://github.com/sindresorhus/gulp-rev
var writeToManifest = function(directory) {
  return lazypipe()
    .pipe(gulp.dest, config.dist + directory)
    .pipe(browserSync.stream, config.browsersync)
    .pipe($.rev.manifest, config.revManifest,config.rev)
    .pipe(gulp.dest, config.dist)();
};

module.exports = {
	writeToManifest: writeToManifest
}