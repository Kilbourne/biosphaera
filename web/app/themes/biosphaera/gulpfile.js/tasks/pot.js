var gulp = require('gulp');
var wpPot = require('gulp-wp-pot');

gulp.task('pot', function() {
    return gulp.src(['templates/**/*.php', 'lib/**/*.php', 'woocommerce/**/*.php', '*.php'
])
        .pipe(wpPot({
            domain: 'sage',
        }))
        .pipe(gulp.dest('sage.pot'));
});
