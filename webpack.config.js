const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'channel-feed/index': path.resolve(
			process.cwd(),
			'src',
			'channel-feed',
			'index.js'
		),
		'channel-browser/index': path.resolve(
			process.cwd(),
			'src',
			'channel-browser',
			'index.js'
		),
	},
};
