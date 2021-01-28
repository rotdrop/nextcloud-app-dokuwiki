const path = require('path');
const webpack = require('webpack');
const xmlReader = require('xml-reader');
const fs = require('fs');



module.exports = {
  entry: {
    app: './src/index.js',
    refresh: './src/refresh.js',
    admin-settings: './src/admin-settings.js',
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'dist'),
  },
  devtool: false,//'source-map',
  plugins: [
    new webpack.DefinePlugin({
      __APP_NAME__: 'dokuwikiembedded',
    }),
  ],
};

/**
 * Local Variables: ***
 * js-indent-level: 2 ***
 * indent-tabs-mode: nil ***
 * End: ***
 */
