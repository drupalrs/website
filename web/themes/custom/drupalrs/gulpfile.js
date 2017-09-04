'use strict';

var config			  = require('./config.js');
var path 			    = require('path');
var upath 			  = require('upath');
var colors		  	= require('colors');
var extend			  = require('extend');
var browserSync	  = require('browser-sync');
var gulp 			    = require('gulp');
var sass 		   	  = require('gulp-sass');
var sourcemaps  	= require('gulp-sourcemaps');
var autoprefixer  = require('gulp-autoprefixer');
var sassGlob 		  = require('gulp-sass-glob');
var notify 		   	= require("gulp-notify");
var gutil 		  	= require("gulp-util");
var imagemin 		  = require('gulp-imagemin');
var cleanCSS      = require('gulp-clean-css');
var cssmin        = require('gulp-cssmin');
var rename 			  = require('gulp-rename');
var drupalBreakpoints = require('drupal-breakpoints-scss');
var reload      	= browserSync.reload;

var themePath = '/themes/custom/' + config.theme + '/css/';
var external = config.external;
var sassCfg = config.sass;
var autoprefixerCfg = config.autoprefixer;
var bsCfg = config.browserSync;
console.log(themePath)
// helper function for pretty priting copied files
function copyLog(src, dest) {
    console.log('[' + colors.gray(new Date().toLocaleTimeString()) + ']' + ' Copied: ' + colors.yellow(path.basename(src)) + ' to ' + colors.green(dest));
}

// find destination folder for the requested file
function findDest(vf, p) {
    var found = false;
    var dest = '';

    for (var i = 0, len = p.length; i < len; i++) {
        if (upath.normalize(vf.path).indexOf(p[i]) !== -1) {
            dest = external.dest[i];
            found = true;
            break ;
        }
    }
    return dest;
}


// Error notify
var reportError = function (error) {
    var lineNumber = (error.lineNumber) ? 'LINE ' + error.lineNumber + ' -- ' : '';
    notify({
        title: 'Task Failed [' + error.plugin + ']',
        message: lineNumber + 'See console.',
        sound: 'Sosumi' // See: https://github.com/mikaelbr/node-notifier#all-notification-options-with-their-defaults
    }).write(error);
    gutil.beep(); // Beep 'sosumi' again
    // Inspect the error object
    //console.log(error);
    // Easy error reporting
    //console.log(error.toString());
    // Pretty error reporting
    var report = '';
    var chalk = gutil.colors.white.bgRed;
    report += chalk('TASK:') + ' [' + error.plugin + ']\n';
    report += chalk('PROB:') + ' ' + error.message + '\n';
    if (error.lineNumber) {
        report += chalk('LINE:') + ' ' + error.lineNumber + '\n';
    }
    if (error.fileName) {
        report += chalk('FILE:') + ' ' + error.fileName + '\n';
    }
    console.error(report);
    // Prevent the 'watch' task from stopping
    this.emit('end');
};


// copy any additional files required by theme
gulp.task('cp', function() {
    if (external === undefined) return ;
    if (external.src === undefined && !(external.src instanceof Array) &&
        external.dest === undefined && !(external.dest instanceof Array)) return ;
    if (external.src.length !== external.dest.length) return ;

    var cleanPaths = [],
        src = external.src;

    for (var i = 0, len = src.length; i < len; i++) {
        cleanPaths.push(path.dirname(src[i]))
    }

    return gulp.src(external.src)
        .pipe(gulp.dest(function (vf) {
            var dest = findDest(vf, cleanPaths)
            copyLog(vf.path, dest);
            return dest;
        }));
});

// run browser sync and watch for changes
gulp.task('serve', ['cp', 'sass:prod'], function() {
    var bsConfig = extend(true, {}, bsCfg, {
        files: [ sassCfg.dest ],
        rewriteRules: [{
            match: new RegExp(themePath, 'g'),
            fn: function (match) {
                var path = sassCfg.dest,
                    startsWith = sassCfg.dest.substr(0, 1);

                if (startsWith === '.') {
                    path =  sassCfg.dest.slice(1);
                }
                else if (startsWith !== '/') {
                    path = '/' + sassCfg.dest;
                }

                return path;
            }
        }]
    });
    browserSync(bsConfig);

    if (sassCfg.enable) {
        gulp.watch(sassCfg.src, ['sass:prod']);
    }
});

// task for building production version css
// * autoprefixer
gulp.task('sass:prod', function () {
    gulp.src(sassCfg.src)
        .pipe(sourcemaps.init())
        .pipe(sassGlob())
        .pipe(sass({sourceComments: false}).on('error', sass.logError))
        .on('error', reportError)
        .pipe(autoprefixer(autoprefixerCfg))
        .pipe(cleanCSS({compatibility: 'ie11'}))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(sassCfg.dest));
});

//add non-optimized images to the dist/images folder
//run task: gulp image
//now you have optimized images in images folder
gulp.task('image', function () {
    gulp.src('dist/images/*')
        .pipe(imagemin())
        .pipe(gulp.dest('images'))
});

// task for building development version css

// task for building breakpoint scss in variables
gulp.task('breakpoint', function () {
    return gulp.src(path.resolve(__dirname, 'drupalrs.breakpoints.yml'))
        .pipe(drupalBreakpoints.ymlToScss())
        .pipe(rename('_breakpoints.scss'))
        .pipe(gulp.dest('./scss/variables/'))
});

// default gulp task
gulp.task('default', ['serve']);
