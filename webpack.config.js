const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const path = require('path');
const webpack = require('webpack');

const distDirectory = path.resolve(__dirname, 'ucms_site/dist');
const extractLess = new ExtractTextPlugin({
  filename: "ucms.min.css",
});

module.exports = {
  entry: './resources/index.js',

  devtool: 'source-map',

  plugins: [
    new CleanWebpackPlugin([
      distDirectory
    ]),
    new webpack.optimize.UglifyJsPlugin({
      sourceMap: 1
    }),
    new webpack.LoaderOptionsPlugin({
      options: {
        jshint: {
          esversion: 6
        }
      }
    }),
    extractLess
  ],

  module: {
    rules: [{
      test: /\.tsx?$/,
      exclude: /node_modules/,
      use: "ts-loader"
    }, {
      test: /\.js$/,
      enforce: "pre",
      exclude: /node_modules/,
      use: "jshint-loader"
    }, {
      test: /\.less$/,
      use: extractLess.extract({
        fallback: "style-loader",
        use: [{
          loader: "css-loader"
        }, {
          loader: "less-loader"
        }]
      })
    }]
  },

  resolve: {
    extensions: [".tsx", ".ts", ".js"]
  },

  output: {
    filename: 'ucms.js',
    path: distDirectory
  }
};
