const path = require( 'path' );
let defaultConfig = require( './node_modules/@wordpress/scripts/config/webpack.config.js' );

//defaultConfig.resolve.alias.vue = 'vue/dist/vue.runtime.esm-browser.prod.js';
module.exports = {
	...defaultConfig,
	entry: {
		backend: path.resolve( process.cwd(), 'src/js', 'index.js' ),
	},
};
