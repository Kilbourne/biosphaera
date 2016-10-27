var gulp        = require('gulp')
  , runSequence  = require('run-sequence')
  ;

gulp.task('build', function(callback) {
  runSequence(
              'font-css',
              'styles',
              'scripts',
              ['fonts', 'images'],
              callback);
});

gulp.task('start', ['clean', 'clean-font-css'], function() {
  gulp.start('watch');
});
gulp.task('default', ['start']);