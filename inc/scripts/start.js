/**
 * Start
 *
 * The create-guten-block CLI starts here.
 *
 * TODO:
 *  - checkRequiredFiles
 *  - printBuildError
 */
"use strict"

// Do this as the first thing so that any code reading it knows the right env.
process.env.BABEL_ENV = "development"
process.env.NODE_ENV = "development"


// Makes the script crash on unhandled rejections instead of silently
// ignoring them. In the future, promise rejections that are not handled will
// terminate the Node.js process with a non-zero exit code.
process.on( "unhandledRejection", err => {
	throw err
} )

const ora = require( "ora" )
const chalk = require( "chalk" )
const webpack = require( "webpack" )
const config = require( "../config/webpack.config.dev" )


// Don't run below node 8.
const currentNodeVersion = process.versions.node
const semver = currentNodeVersion.split( "." )
const major = semver[ 0 ]

// If below Node 8.
if ( major < 8 ) {
	console.error(
		chalk.red(
			"You are running Node " +
				currentNodeVersion +
				".\n" +
				"Starter Sites requires Node 8 or higher. \n" +
				"Kindly, update your version of Node."
		)
	)
	process.exit( 1 )
}

// Init the spinner.
const spinner = new ora( { text: "" } )


// Create the production build and print the deployment instructions.
async function build( webpackConfig ) {
	// Compiler Instance.
	const compiler = await webpack( webpackConfig )
	
	// Run the compiler.
	compiler.watch( {}, ( err, stats ) => {
		//clearConsole();

		var stats_json = stats.toJson( {}, true )

		console.log( stats_json.errors )
		console.log( stats_json.warnings )

		// Start the build.
		console.log( `\n${ chalk.dim( "Let's build and compile the files..." ) }` )
		console.log( "\n✅ ", chalk.black.bgGreen( " Compiled successfully! \n" ) )
		console.log(
			chalk.dim( "   Note that the development build is not optimized. \n" ),
			chalk.dim( "  To create a production build, use" ),
			chalk.green( "npm" ),
			chalk.white( "run build\n" )
		)
		return spinner.start(
			`${ chalk.dim( "Watching for changes... (Press CTRL + C to stop)." ) }`
		)
	} )
	
	
}

build( config )
