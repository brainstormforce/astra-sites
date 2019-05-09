module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';

    var pkg = grunt.file.readJSON('package.json');

	// Project configuration
	grunt.initConfig( {

		addtextdomain: {
			options: {
				textdomain: 'astra-sites',
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!php-tests/**',
						'!bin/**',
						'!inc/importers/class-widgets-importer.php',
						'!inc/importers/wxr-importer/class-logger.php',
						'!inc/importers/wxr-importer/class-wxr-importer.php'
					]
				}
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'astra-sites.php',
					potFilename: 'astra-sites.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		copy: {
                main: {
                    options: {
                        mode: true
                    },
                    src: [
                        '**',
                        '*.zip',
                        '!node_modules/**',
                        '!build/**',
                        '!css/sourcemap/**',
                        '!.git/**',
                        '!bin/**',
                        '!.gitlab-ci.yml',
                        '!bin/**',
                        '!tests/**',
                        '!phpunit.xml.dist',
                        '!*.sh',
                        '!*.map',
                        '!Gruntfile.js',
                        '!package.json',
                        '!.gitignore',
                        '!phpunit.xml',
                        '!README.md',
                        '!sass/**',
                        '!codesniffer.ruleset.xml',
                        '!vendor/**',
                        '!composer.json',
                        '!composer.lock',
                        '!package-lock.json',
                        '!phpcs.xml.dist',
                    ],
                    dest: 'astra-sites/'
                }
        },

        compress: {
            main: {
                options: {
                    archive: 'astra-sites-' + pkg.version + '.zip',
                    mode: 'zip'
                },
                files: [
                    {
                        src: [
                            './astra-sites/**'
                        ]

                    }
                ]
            }
        },

		clean: {
            main: ["astra-sites"],
            zip: ["astra-sites.zip"]

        },

	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-compress' );
    grunt.loadNpmTasks( 'grunt-contrib-clean' );

    // Generate README.md file.
    grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

    // Generate .pot file.
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );

	// Grunt release - Create installable package of the local files
    grunt.registerTask('release', ['clean:zip', 'copy', 'compress', 'clean:main']);

	grunt.util.linefeed = '\n';

};
