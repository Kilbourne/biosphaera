var gulp        = require('gulp')
  , $           = require('gulp-load-plugins')({ camelize: true })
  , config      = require('../gulpconfig').fonts
  , browserSync = require("browser-sync").get('My server')
  , del         = require('del')
;

gulp.task('fonts', function() {
  return gulp.src(config.globs)
    .pipe($.flatten())
    .pipe(gulp.dest(config.dist))
    .pipe(browserSync.stream());
});

gulp.task ('font-css', function() {
  return gulp.src([config.src + config.css ]) //Gather up all the 'stylesheet.css' files
     .pipe($.concatUtil(config.scssName)) //Concat them all into a single file
     .pipe($.concatUtil.header(config.header))
     .pipe($.replace("url('", "url('"+config.urlReplace))
     .pipe(gulp.dest(config.scssDest)); // Put them in the assets/styles/components folder
});

// Remove the font-face SCSS file if a cleanup is run
gulp.task('clean-font-css', del.bind(null, [config.scssDest+'/'+config.scssName]));

gulp.task('watch-font-css', function() {
  gulp.watch([config.src + config.css], ['font-css']);
});

gulp.task('watch-fonts', function() {
  gulp.watch([config.src + '/**/*'], ['fonts']);
});
