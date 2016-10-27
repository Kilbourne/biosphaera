var gulp        = require('gulp')
  , $           = require('gulp-load-plugins')({ camelize: true })
  , config      = require('../gulpconfig').images
  , browserSync = require("browser-sync").get('My server')
;

gulp.task('images', function() {
  return gulp.src(config.globs)
    .pipe($.imagemin(config.imagemin))
    .pipe(gulp.dest(config.dist))
    .pipe(browserSync.stream());
});

gulp.task('watch-images', function() {
  gulp.watch([config.src + '/**/*'], ['images']);
});
