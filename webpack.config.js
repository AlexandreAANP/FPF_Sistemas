const path = require('path');

module.exports = {
    entry: [
        __dirname + '/assets/js/app.js',
        __dirname + '/assets/scss/app.scss'
    ],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: 'js/app.min.js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: ['babel-loader'],
            }, {
                test: /\.scss$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'file-loader',
                        options: { outputPath: 'css', name: '[name].min.css'}
                    },
                    'sass-loader'
                ]
            }
        ]
    }
};