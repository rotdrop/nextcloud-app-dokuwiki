const path = require('path');
const webpack = require('webpack');
const xmlReader = require('xml-reader');
const fs = require('fs');

function appName() {
  const infoFile = path.join(__dirname, 'appinfo/info.xml');
  const xmlData = fs.readFileSync(infoFile);
  const result = xmlReader.parseSync(xmlData.toString());
  for (const child of result.children) {
    if (child.name === 'id') {
      return child.children[0].value;
    }
  }
  throw new Error('App-Name not found in ' + infoFile);
}

module.exports = {
  entry: {
    app: './src/index.js',
    refresh: './src/refresh.js',
    'admin-settings': './src/admin-settings.js',
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'js'),
  },
  devtool: false, // 'source-map',
  plugins: [
    new webpack.DefinePlugin({
      __APP_NAME__: JSON.stringify(appName())
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
    }),
  ],
};

/**
 * Local Variables: ***
 * js-indent-level: 2 ***
 * indent-tabs-mode: nil ***
 * End: ***
 */
