module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.registerTask('default',['watch']);

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		sass: {
			dist: {
				files: {
					'web/assets/css/style.css' : 'source/sass/style.scss'
				}
			}
		},
		watch: {
			css: {
				files: 'source/sass/*.scss',
				tasks: ['sass'],
				options: {
					livereload: true,
				}
			}
		},
		concat: {
			options: {
				separator: ';',
			},
			dist: {
				src: [
					'bower_components/jquery/dist/jquery.js',
					'bower_components/uikit/js/uikit.js',
					'bower_components/uikit/js/components/nestable.js',
					'bower_components/humanize-plus/public/src/humanize.js',
					'bower_components/hogan/web/builds/3.0.2/hogan-3.0.2.min.js',
					'web/assets/js/app.js',
				],
				dest: 'web/assets/js/built.js',
			},
		},
	});

};