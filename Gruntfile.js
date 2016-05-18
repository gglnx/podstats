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
				tasks: ['coffee:dist']
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
					middleware: function(connect, options) {
						var middlewares = [];
						var directory = options.directory || options.base[options.base.length - 1];

						if ( !Array.isArray(options.base ) )
							options.base = [options.base];

						// PHP backend
						require('get-port')(function (error, port) {
							var phpServer = require('php-built-in-server');
							var server = new phpServer();

							server.on('listening', function (event) {
								console.log('[LISTENING]', event.host.address + ':' + event.host.port);

								var proxy = require('grunt-connect-proxy/lib/utils');
								var proxyConfig = {
									context: '/',
									host: '127.0.0.1',
									port: event.host.port,
									https: false,
									xforward: true,
									rules: [],
									ws: false,
									headers: {
										'Host': 'localhost:9000'
									}
								};

								proxy.registerProxy({
									server: require('http-proxy').createProxyServer({
										target: proxyConfig,
										secure: proxyConfig.https,
										xfwd: proxyConfig.xforward
									}),
									config: proxyConfig
								});

								middlewares.push( proxy.proxyRequest );
							});

							server.on('error', function (event) {
								console.log('[ERROR]', event.error.toString());
							});

							server.listen( require('path').resolve('public'), port, '127.0.0.1', require('path').resolve('public/index.php'), {
								'log_errors': '1',
								'error_log': require('path').resolve('logs/errors.log'),
								'date.timezone': 'Europe/Berlin'
							} );
						});

						// Livereload
						middlewares.push( require('connect-livereload')({
							port: 35729
						}) );

						// Serve generated files
						middlewares.push( connect.static(require('path').resolve('.tmp')) );

						// Serve static content
						//middlewares.push( connect.static(require('path').resolve(paths.public)) );
						middlewares.push( connect.static(require('path').resolve(paths.assets)) );

						return middlewares;
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
						'!<%= paths.public %>/index.php*',
						'!<%= paths.public %>/fonts*',
						'!<%= paths.public %>/stylesheets*'
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
			dist: {
				options: {
					'baseUrl': '.tmp/javascripts',
					'mainConfigFile': '.tmp/javascripts/main.js',
					'out': 'public/javascripts/main.js',
					'name': 'main'
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
		'copy:dist'
	]);

	// Default task
	grunt.registerTask('default', 'server');
}
