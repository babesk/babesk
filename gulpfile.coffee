gulp = require 'gulp'

watchify = require 'gulp-watchify'
uglify = require 'gulp-uglify'
streamify = require 'gulp-streamify'
cjsx = require 'gulp-cjsx'
gutil = require 'gulp-util'
watch = require 'gulp-watch'
plumber = require 'gulp-plumber'
rename = require 'gulp-rename'
header = require 'gulp-header'
runSequence = require 'run-sequence'

bundlePaths =
  src: [
    'code/include/js/modules/**/*.js'
    '!code/include/js/modules/**/lib/**'
  ]
  dest: 'code/include/js/dist'

# Hack to enable configurable watchify watching
watching = false
gulp.task 'enable-watch-mode', -> watching = true

# Browserify and copy js files
gulp.task 'browserify', watchify((watchify)->
  return gulp.src bundlePaths.src
    .pipe watchify(
      watch: watching
    )
    .pipe streamify(uglify())
    .pipe gulp.dest(bundlePaths.dest)
)

# Compile Coffeescript to Javascript
gulp.task 'cjsx', ->
  gulp.src './code/include/cjs/**/*.cjsx'
    .pipe cjsx(bare: true).on('error', gutil.log)
    .pipe header('//Generated with Coffeescript\n')
    .pipe gulp.dest('./code/include/js')

# Compile Coffeescript to Javascript and watch for changes to existing files
gulp.task 'cjsx-watch', ->
  gulp.src './code/include/cjs/**/*.cjsx'
    .pipe watch('./code/include/cjs/**/*.cjsx')
    .pipe plumber()
    .pipe cjsx(bare: true).on('error', gutil.log)
    .pipe header('//Generated with Coffeescript\n')
    .pipe rename((path)->
      path.dirname = path.dirname.replace 'cjs/', 'js/'
      undefined
    )
    .pipe gulp.dest('./code/include/js')

gulp.task 'watchify', ['enable-watch-mode', 'browserify']

# Compile & Watch: Coffeescript to Javascript, bundle and uglify it
gulp.task 'watch', -> runSequence 'cjsx', ['cjsx-watch', 'watchify']

# The default task (called when you run 'gulp' from cli)
gulp.task 'default', ['watch']