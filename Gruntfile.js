/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */
'use strict';

/**
 * Livereload
 */
var LIVERELOAD_PORT = 35729;
var lrSnippet = require('connect-livereload')({ port: LIVERELOAD_PORT });
var mountFolder = function (connect, dir) {
	return connect.static(require('path').resolve(dir));
};

/*
 * Use PHP as backend
 */
var gateway = require('gateway');
var modRewrite = require('connect-modrewrite');
var phpGateway = function (dir) {
	return gateway(require('path').resolve(dir), {
		'.php': 'php-cgi'
	});
};

/**
 * Cross-Origin Resource Sharing middleware
 */
var cors = require('cors');

/**
 * Configure grunt
 */
module.exports = function(grunt) {
	// Load all grunt tasks
	require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

	// Paths
	var paths = {
		app: 'Application',
		assets: 'Application/Assets',
		public: 'public'
	};

	// Init configuration for grunt
	grunt.initConfig({
		// Paths
		paths: paths,

		// Watch files and live reload
		watch: {
			coffee: {
				files: ['<%= paths.assets %>/javascripts/{,**/}*.coffee'],
				tasks: ['newer:coffee:dist']
			},
			styles: {
				files: ['<%= paths.assets %>/stylesheets/{,*/}*.css'],
				tasks: ['copy:styles']
			},
			less: {
				files: ['<%= paths.assets %>/stylesheets/{,**/}*.less'],
				tasks: ['less:server']
			},
			livereload: {
				options: {
					livereload: LIVERELOAD_PORT
				},
				files: [
					'<%= paths.app %>/Views/{,*/}*.twig',
					'.tmp/stylesheets/{,*/}*.css',
					'{.tmp,<%= paths.assets %>}/javascripts/{,*/}*.js',
					'<%= paths.assets %>/images/{,*/}*.{png,jpg,jpeg,gif,webp,svg}'
				]
			}
		},

		// Local developement server
		connect: {
			options: {
				port: 9000,
				hostname: 'localhost'
			},
			livereload: {
				options: {
					middleware: function (connect) {
						return [
							cors(),
							mountFolder(connect, '.tmp'),
							mountFolder(connect, paths.assets),
							mountFolder(connect, paths.public),
							lrSnippet,
							modRewrite([
								'^(.+)$ /index.dev.php?$1'
							]),
							phpGateway('public')
						];
					}
				}
			}
		},

		// Open development server in browser
		open: {
			server: {
				path: 'http://localhost:<%= connect.options.port %>'
			}
		},

		// Cleaning
		clean: {
			dist: {
				files: [{
					dot: true,
					src: [
						'.tmp',
						'<%= paths.public %>/*',
						'!<%= paths.public %>/.git*',
						'!<%= paths.public %>/index*'
					]
				}]
			},
			server: '.tmp'
		},

		// LESS
		less: {
			server: {
				options: {
					paths: ['<%= paths.assets %>/stylesheets'],
					dumpLineNumbers: 'comments'
				},
				files: {
					'.tmp/stylesheets/main.css': '<%= paths.assets %>/stylesheets/main.less'
				}
			},
			dist: {
				options: {
					paths: ['<%= paths.app %>/Assets/stylesheets'],
					cleancss: true
				},
				files: {
					'<%= paths.public %>/stylesheets/main.css': '<%= paths.assets %>/stylesheets/main.less'
				}
			}
		},

		// Symlinking
		symlink: {
			explicit: {
				src: '<%= paths.assets %>/components',
				dest: '.tmp/components'
			}
		},

		// CoffeeScript
		coffee: {
			dist: {
				files: [{
					expand: true,
					cwd: '<%= paths.assets %>/javascripts',
					src: '{,*/}*.coffee',
					dest: '.tmp/javascripts',
					ext: '.js'
				}]
			},
		},

		// RequireJS
		requirejs: {
			options: {
				'appDir': '.tmp/javascripts',
				'mainConfigFile': '.tmp/javascripts/common.js',
				'dir': '.tmp/javascripts-build',
				'optimize': 'uglify2',
				'normalizeDirDefines': 'skip',
				'skipDirOptimize': true
			},
			dist: {
				options: {
					'modules': [
						{
							'name': 'common',
						},
						{
							'name': 'main',
							'exclude': ['common'],
						}
					]
				}
			}
		},

		// Imagemin
		imagemin: {
			dist: {
				files: [{
					expand: true,
					cwd: '<%= paths.assets %>/images',
					src: '{,*/}*.{png,jpg,jpeg}',
					dest: '<%= paths.public %>/images'
				}]
			}
		},

		// SVGmin
		svgmin: {
			dist: {
				files: [{
					expand: true,
					cwd: '<%= paths.assets %>/images',
					src: '{,*/}*.svg',
					dest: '<%= paths.public %>/images'
				}]
			}
		},

		// Copy static files
		copy: {
			dist: {
				files: [{
					expand: true,
					dot: true,
					cwd: '<%= paths.assets %>',
					dest: '<%= paths.public %>',
					src: [
						'*.{ico,png,txt}',
						'images/{,*/}*.{webp,gif}',
						'components/requirejs/require.js',
						'stylesheets/fonts/{,*/}*.{eot,svg,ttf,woff}',
					]
				}]
			},
			styles: {
				expand: true,
				dot: true,
				cwd: '<%= paths.assets %>/stylesheets',
				dest: '.tmp/stylesheets/',
				src: '{,*/}*.css'
			},
			javascripts: {
				expand: true,
				dot: true,
				cwd: '<%= paths.assets %>/javascripts',
				dest: '.tmp/javascripts/',
				src: '{,*/}*.js'
			},
			packed_javascripts: {
				expand: true,
				dot: true,
				cwd: '.tmp/javascripts',
				dest: '<%= paths.public %>/javascripts/',
				src: '*.js'
			}
		},

		// Webfont
		webfont: {
			icons: {
				src: '<%= paths.assets %>/icons/*.svg',
				dest: '<%= paths.assets %>/fonts',
				destCss: '<%= paths.assets %>/stylesheets/components',
				options: {
					syntax: 'bootstrap',
					template: '<%= paths.assets %>/icons/tmpl.css',
					stylesheet: 'less',
					relativeFontPath: '../fonts/',
					htmlDemo: false
				}
			}
		},

		// Concurrent: Do some task at the same time to save some time
		concurrent: {
			server: [
				'less:server',
				'coffee:dist',
				'configureRewriteRules',
				'copy:styles'
			],
			dist: [
				'coffee',
				'less:dist',
				'copy:styles',
				'imagemin',
				'symlink',
				'svgmin'
			]
		}
	});

	// Task: server (while development)
	grunt.registerTask('server', [
		'clean:server',
		'concurrent:server',
		'connect:livereload',
		'open',
		'watch'
	]);

	// Task: build (build for production)
	grunt.registerTask('build', [
		'clean:server',
		'clean:dist',
		'copy:javascripts',
		'concurrent:dist',
		'requirejs',
		'copy:dist',
		'copy:packed_javascripts'
	]);

	// Default task
	grunt.registerTask('default', 'server');
}
