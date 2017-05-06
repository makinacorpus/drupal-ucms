var
  gulp  = require('gulp'),
  rename = require('gulp-rename'),
  cssmin  = require('gulp-clean-css'),
  less    = require('gulp-less')
;

// LESS compilation
gulp.task('less', function () {
  var pipe = gulp.src('resources/less/style.less');
  pipe = pipe
    .pipe(less())
    .pipe(cssmin())
    .pipe(rename("ucms.min.css"));
  ;
  return pipe
    .pipe(gulp.dest('ucms_site/dist/'))
    .on('error', errorHandler)
  ;
});

gulp.task('default', ['less']);

// Handle the error
function errorHandler(error) {
  console.log(error.toString());
  this.emit('end');
}
