/**
 * Webpack Configuration
 *
 * Working of a Webpack can be very simple or complex. This is an intenally simple
 * build configuration.
 *
 * Webpack basics — If you are new the Webpack here's all you need to know:
 *     1. Webpack is a module bundler. It bundles different JS modules together.
 *     2. It needs and entry point and an ouput to process file(s) and bundle them.
 *     3. By default it only understands common JavaScript but you can make it
 *        understand other formats by way of adding a Webpack loader.
 *     4. In the file below you will find an entry point, an ouput, and a babel-loader
 *        that tests all .js files excluding the ones in node_modules to process the
 *        ESNext and make it compatible with older browsers i.e. it converts the
 *        ESNext (new standards of JavaScript) into old JavaScript through a loader
 *        by Babel.
 *
 * TODO: Instructions.
 *
 * @since 1.0.0
 */

const paths = require( "./paths" )
const webpack = require( "webpack" )
const autoprefixer = require( "autoprefixer" )
const ExtractTextPlugin = require( "extract-text-webpack-plugin" )

// Source maps are resource heavy and can cause out of memory issue for large source files.
const shouldUseSourceMap = process.env.GENERATE_SOURCEMAP === "true"

const fs = require( "fs" )
const sass = require('node-sass');

// Configuration for the ExtractTextPlugin — DRY rule.
const extractConfig = {
	use: [
		// "postcss" loader applies autoprefixer to our CSS.
		{ loader: "raw-loader" },
		{
			loader: "postcss-loader",
			options: {
				ident: "postcss",
				plugins: [
					autoprefixer( {
						browsers: [
							">1%",
							"last 4 versions",
							"Firefox ESR",
							"not ie < 9", // React doesn't support IE8 anyway
						],
						flexbox: "no-2009",
					} ),
				],
			},
		}
	],
}

// Export configuration.
module.exports = {
	entry: {
		"./inc/assets/js/dist/index": paths.pluginBlocksJs, // 'name' : 'path/file.ext'.
	},
	output: {
		// Add /* filename */ comments to generated require()s in the output.
		pathinfo: true,
		// The dist folder.
		path: paths.pluginDist,
		filename: "[name].js", // [name] = './assets/js/dist/blocks.build' as defined above.
	},
	// You may want 'eval' instead if you prefer to see the compiled output in DevTools.
	devtool: shouldUseSourceMap ? "source-map" : false,
	module: {
		rules: [
			{
				test: /\.(js|jsx|mjs)$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: "babel-loader",
					options: {
						
						// This is a feature of `babel-loader` for webpack (not Babel itself).
						// It enables caching results in ./node_modules/.cache/babel-loader/
						// directory for faster rebuilds.
						cacheDirectory: true,
					},
				},
			}
		],
	},
	stats: "minimal"
}
