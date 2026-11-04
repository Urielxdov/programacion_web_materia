module.exports = {
	globDirectory: '.',
	globPatterns: [
		'**/*.{jpg,json,html,png,pdf}'
	],
	swDest: 'sw.js',
	ignoreURLParametersMatching: [
		/^utm_/,
		/^fbclid$/
	]
};