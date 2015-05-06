gulp = require 'gulp'

concat = require 'gulp-concat'
watchify = require 'gulp-watchify'
uglify = require 'gulp-uglify'
streamify = require 'gulp-streamify'
cjsx = require 'gulp-cjsx'
gutil = require 'gulp-util'
watch = require 'gulp-watch'
plumber = require 'gulp-plumber'
header = require 'gulp-header'

# Only bundle files with browserify that are in the subfolder modules.
bundlePaths =
  src: [
    'code/include/js/modules/**/*.js'
    '!code/include/js/modules/**/lib/**'
  ]
  dest: 'code/include/js/dist'

baseBundlePaths =
  src: [
    'code/include/js/vendor/jquery.min.js'
    'code/include/js/vendor/bootstrap.min.js'
    'code/include/js/vendor/toastr.min.js'
    'code/include/js/vendor/json2.min.js'
    'code/include/js/custom-base.js'
  ]
  dest: 'code/include/js/dist'

# Hack to enable configurable watchify watching
watching = false
gulp.task 'enable-watch-mode', -> watching = true

# Browserify and copy js files
gulp.task 'browserify', watchify((watchify)->
  return gulp.src bundlePaths.src
    .pipe plumber()
    .pipe watchify(
      watch: watching
      # Browserifys baseDir is in code/include/js/modules
      paths: ['./']
    )
    .pipe streamify(uglify())
    .pipe gulp.dest(bundlePaths.dest)
)

# Compile Coffeescript to Javascript
gulp.task 'cjsx', ->
  gulp.src './code/include/cjs/**/*.cjsx'
    .pipe cjsx(bare: true).on('error', gutil.log)
    .pipe header('// Generated by Coffeescript\n')
    .pipe gulp.dest('./code/include/js')

# Compile Coffeescript to Javascript and watch for changes to existing files
gulp.task 'cjsx-watch', ->
  gulp.src './code/include/cjs/**/*.cjsx'
    .pipe plumber()
    .pipe watch(
      './code/include/cjs/**/*.cjsx'
      ignoreInitial: false
      verbose: true
      base: 'code/include/cjs'
    )
    .pipe cjsx(bare: true).on('error', gutil.log)
    .pipe header('// Generated by Coffeescript\n')
    .pipe gulp.dest('./code/include/js')

gulp.task 'watchify', ['enable-watch-mode', 'browserify']

# Concatenate the most used files into one file that always gets served
# Do not do this with Browserify yet because the components used are not
# CommonJS-compliant
gulp.task 'base-bundle', ->
  gulp.src baseBundlePaths.src
    .pipe concat('base-bundle.js')
    .pipe streamify(uglify())
    .pipe gulp.dest(baseBundlePaths.dest)


# Compile & Watch: Coffeescript to Javascript, bundle and uglify it
gulp.task 'watch', ['cjsx-watch', 'watchify']

# The default task (called when you run 'gulp' from cli)
gulp.task 'default', ['watch']