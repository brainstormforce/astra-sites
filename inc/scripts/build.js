/**
 * Build
 *
 * The create-guten-block CLI builds here.
 */

"use strict"

// Do this as the first thing so that any code reading it knows the right env.
process.env.BABEL_ENV = "production"
process.env.NODE_ENV = "production"

// Makes the script crash on unhandled rejections instead of silently
// ignoring them. In the future, promise rejections that are not handled will
// terminate the Node.js process with a non-zero exit code.
process.on( "unhandledRejection", err => {
	throw err
} )

// Modules.
const fs = require( "fs" )
const ora = require( "ora" )
const path = require( "path" )
const chalk = require( "chalk" )
const webpack = require( "webpack" )
const fileSize = require( "filesize" )
const gzipSize = require( "gzip-size" )
const config = require( "../config/webpack.config.prod" )

// Build file paths.
const theCWD = process.cwd()
const fileBuildJS = path.resolve( theCWD, "inc/assets/js/dist/index.js" )

/**
 * Get File Size
 *
 * Get filesizes of all the files.
 *
 * @param {string} filePath path.
 * @returns {string} then size result.
 */
const getFileSize = filePath => {
	return fileSize( gzipSize.sync( fs.readFileSync( filePath ) ) )
}

// clearConsole();

// Init the spinner.
const spinner = new ora( { text: "" } )

/**
 * Build function
 *
 * Create the production build and print the deployment instructions.
 *
 * @param {json} webpackConfig config
 */
async function build( webpackConfig ) {

	// Compiler Instance.
	const compiler = await webpack( webpackConfig )

	// Run the compiler.
	compiler.run( ( err, stats ) => {

		var stats_json = stats.toJson( {}, true )

		console.log( stats_json.errors )
		console.log( stats_json.warnings )

		// Start the build.
		console.log( `\n ${ chalk.dim( "Let's build and compile the files..." ) }` )
		console.log( "\n✅ ", chalk.black.bgGreen( " Built successfully! \n" ) )

		console.log(
			"\n\n",
			"File sizes after gzip:",
			"\n\n",
			getFileSize( fileBuildJS ),
			`${ chalk.dim( "— inc/assets/js/dist/" ) }`,
			`${ chalk.green( "index.js" ) }`,
			"\n\n"
		)

		return true
	} )
}

build( config )
