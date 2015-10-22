var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sourcemaps = require('gulp-sourcemaps');
var notify = require('gulp-notify');
var minifyCSS = require('gulp-minify-css');
var autoprefixer = require('gulp-autoprefixer');
var less = require('gulp-less');
var rework = require('gulp-rework');
var reworkUrl = require('rework-plugin-url');
var shell = require('gulp-shell');

var path = {
    app: "app/Resources/public/",
    web: "web/"
};

var paths = {
    js: {
        site: [
            path.app + 'js/**/*.js',
            '!' + path.app + 'js/ext/**/*.js',
            path.web + 'bundles/fosjsrouting/js/router.js'
        ],
        ext: [
            path.app + 'lib/moment/min/moment.min.js',
            path.app + 'lib/moment/locale/fr.js',
            path.app + 'lib/bootstrap-progressbar/bootstrap-progressbar.min.js',
            path.app + 'lib/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
            path.app + 'lib/datatables-responsive/js/dataTables.responsive.js',
            path.app + 'lib/webui-popover/dist/jquery.webui-popover.min.js',
            path.app + 'lib/select2/dist/js/i18n/fr.js',
            path.app + 'js/ext/**/*.js'
        ],
        bigExt: [
            path.app + 'lib/jquery/dist/jquery.min.js',
            path.app + 'lib/bootstrap/dist/js/bootstrap.min.js',
            path.app + 'lib/datatables/media/js/jquery.dataTables.min.js',
            path.app + 'lib/select2/dist/js/select2.min.js'
        ]
    },
    css: {
        site: [
            path.app + 'less/design.less',
            path.app + 'css/font-physbook.css'
        ],
        connexion: [
            path.app + 'less/design-connexion.less',
            path.app + 'css/font-physbook.css'
        ],
        ext: [
            path.app + 'css/animations.css',
            path.app + 'css/dataTables.bootstrap.css',
            path.app + 'lib/datatables-responsive/css/dataTables.responsive.css',
            path.app + 'lib/select2/dist/css/select2.css',
            path.app + 'lib/select2-bootstrap-theme/dist/select2-bootstrap.min.css',
            path.app + 'lib/webui-popover/dist/jquery.webui-popover.min.css',
            path.app + 'lib/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css'
        ]
    }
};

gulp.task('default', ['dev']);

gulp.task('dev', ['generate', 'watch'], function() {
    return gulp.src('')
        .pipe(notify("Ready :)"));
});

gulp.task('prod', ['generate'], shell.task([
  'php app/console fos:js-routing:dump --env=prod', // generate JS routes
  'php app/console assetic:dump --env=prod --no-debug' // for 3rd party bundles (eg. FOSCommentBundle)
]));

gulp.task('watch', function () {
    var onChange = function (event) {
        console.log('File ' + event.path + ' has been ' + event.type + '.');
    };

    gulp.watch(paths.js.site, ['compress:js:site'])
        .on('change', onChange);
    gulp.watch(path.app + "less/**/*.less", ['compress:css:site'])
        .on('change', onChange);
    gulp.watch(paths.css.connexion, ['compress:css:connexion'])
        .on('change', onChange);
});

gulp.task('generate', ['copy', 'compress']);
gulp.task('copy', ['copy:js']);
gulp.task('compress', ['compress:js', 'compress:css']);
gulp.task('compress:js', ['compress:js:site', 'compress:js:ext']);
gulp.task('compress:css', ['compress:css:site', 'compress:css:connexion', 'compress:css:ext']);

gulp.task('copy:js', function() {
   return gulp.src(paths.js.bigExt)
       .pipe(gulp.dest(path.web + 'js/ext'))
});

gulp.task('compress:js:site', function () {
    return gulp.src(paths.js.site)
        .pipe(sourcemaps.init())
        .pipe(concat('site.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(path.web + 'js'));
});

gulp.task('compress:js:ext', function () {
    return gulp.src(paths.js.ext)
        .pipe(concat('ext.js'))
        .pipe(uglify())
        .pipe(gulp.dest(path.web + 'js'));
});

gulp.task('compress:css:site', function() {
    return gulp.src(paths.css.site)
        .pipe(sourcemaps.init())
        .pipe(less())
        .pipe(rework(reworkUrl(function(url) {
            return '../' + url;
        })))
        .pipe(minifyCSS())
        .pipe(autoprefixer('last 2 versions'))
        .pipe(concat('site.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(path.web + 'css'))
});

gulp.task('compress:css:connexion', function() {
    return gulp.src(paths.css.connexion)
        .pipe(sourcemaps.init())
        .pipe(less())
        .pipe(rework(reworkUrl(function(url) {
            return '../' + url;
        })))
        .pipe(minifyCSS())
        .pipe(autoprefixer('last 2 versions'))
        .pipe(concat('connexion.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(path.web + 'css'))
});

gulp.task('compress:css:ext', function() {
    return gulp.src(paths.css.ext)
        .pipe(sourcemaps.init())
        .pipe(minifyCSS())
        .pipe(autoprefixer('last 2 versions'))
        .pipe(concat('ext.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(path.web + 'css'))
});