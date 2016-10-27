var gulp        = require('gulp')
  , del         = require('del')  
  , config      = require('../gulpconfig').util
  ;

// ### Clean
// `gulp clean` - Deletes the build folder entirely.
gulp.task('clean', del.bind(null, config.clean));