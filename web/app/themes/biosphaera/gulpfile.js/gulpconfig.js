var   argv             = require('minimist')(process.argv.slice(2))
	, devUrl		   = require('./devUrl')
	, manifestSrc      = './assets/manifest.json'
	, enabled = {
		  // Enable static asset revisioning when `--production`
		  rev: argv.production,
		  // Disable source maps when `--production`
		  maps: !argv.production,
		  // Fail styles task on error when `--production`
		  failStyleTask: argv.production,
		  // Fail due to JSHint warnings only when `--production`
		  failJSHint: argv.production,
		  // Strip debug statments from javascript when `--production`
		  stripJSDebug: argv.production
	  }
	, supportedBrowser = [
        'last 2 versions',
        'ie 8',
        'ie 9',
        'android 2.3',
        'android 4',
        'opera 12'
      ]
;

var manifest = require('asset-builder')(manifestSrc)
// `path` - Paths to base asset directories. With trailing slashes.
// - `path.source` - Path to the source files. Default: `assets/`
// - `path.dist` - Path to the build directory. Default: `dist/`
var path = manifest.paths;
var source = path.source;
var dist = path.dist ;

// `config` - Store arbitrary configuration values here.
var config = manifest.config || {};

// `globs` - These ultimately end up in their respective `gulp.src`.
// - `globs.js` - Array of asset-builder JS dependency objects. Example:
//   ```
//   {type: 'js', name: 'main.js', globs: []}
//   ```
// - `globs.css` - Array of asset-builder CSS dependency objects. Example:
//   ```
//   {type: 'css', name: 'main.css', globs: []}
//   ```
// - `globs.fonts` - Array of font path globs.
// - `globs.images` - Array of image path globs.
// - `globs.bower` - Array of all the main Bower files.
var globs = manifest.globs;

// `project` - paths to first-party assets.
// - `project.js` - Array of first-party JS assets.
// - `project.css` - Array of first-party CSS assets.
var project = manifest.getProjectGlobs();

module.exports = {
	  enabled 	  : enabled
	, browsersync : {
	      files: ['{lib,templates}/**/*.php', '*.php'],
	      proxy: devUrl,
	      snippetOptions: {
	      	whitelist: ['/wp-admin/admin-ajax.php'],
	      	blacklist: ['/wp-admin/**']
	      }
	  }
	, watch     : {
		  configFiles: ['bower.json', 'assets/manifest.json']
	}
	, manifest  : {
		  dist        : dist
		, revManifest : dist + 'assets.json'
		, browsersync : {match: '**/*.{js,css}'}
		, rev         : { base: dist,merge: true }
		, manifest     : manifest
	}
	, scripts   : {
		  src          :  source + 'scripts'
		, uglify       :  {
						      compress: {
						        'drop_debugger': enabled.stripJSDebug
						      }
						   }
		, project      : project.js
		, sourceMap    : {
					      sourceRoot: source + 'scripts/'
					     }
		, globsBase    : 'scripts'
		, manifestDir  : 'scripts'
		, otherHint    : [ 'bower.json', 'gulpfile.js' ]
	}
	, styles    : {
		  src     	   : source + 'styles'
		, project 	   : project.css
		, browsers 	   : supportedBrowser
		, sass    	   : {
				          outputStyle: 'nested', // libsass doesn't support expanded yet
				          precision: 10,
				          includePaths: ['.',require('node-bourbon').includePaths],
				          errLogToConsole: !enabled.failStyleTask
				        }
		, minify  	   : {
					      advanced: false,
					      rebase: false
					     }
		, sourceMap    : {
					      sourceRoot: source + 'styles/'
					     }
		, globsBase    : 'styles'
		, manifestDir  : 'styles'
	  }
	, images    : {
		  dist     : dist + 'images'
		, globs    : globs.images
		, imagemin : {
      		progressive: true,
      		interlaced: true,
      		svgoPlugins: [{removeUnknownsAndDefaults: false}, {cleanupIDs: false}]
    	  }
	}
	, fonts      : {
		  src        : source + 'fonts'
		, dist       : dist + 'fonts'
		, globs      : globs.fonts
		, css        : '/**/*.css'
		, scssName   : '_fonts.scss'
		, header     : '/* !!! WARNING !!! \nThis file is auto-generated. \nDo not edit it or else you will lose changes next time you compile! */\n\n'
		, scssDest   : source + ['styles/components']
		, urlReplace : "../fonts/"
	}
	,  util	     : {
		  clean : [dist]
	}
}
