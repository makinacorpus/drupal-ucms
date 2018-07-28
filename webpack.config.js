// const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const MinifyPlugin = require("babel-minify-webpack-plugin");
const path = require('path');
const webpack = require('webpack');

const SRC_DASHBOARD = path.resolve(__dirname, 'ucms_dashboard/src-front');
// const DIST_DASHBOARD = path.resolve(__dirname, 'ucms_dashboard/dist');
const extractLess = new ExtractTextPlugin({filename: "[name]/dist/style.css"});

module.exports = {
  plugins: [
    // new CleanWebpackPlugin([DIST_DASHBOARD]),
    // new MinifyPlugin(),
    extractLess
  ],
  module: {
    rules: [{
      test: /\.tsx?$/,
      exclude: /node_modules/,
      use: [{
        loader: "babel-loader"
      }, {
        loader: "ts-loader"
      }],
    },{
      test: /\.jsx?$/,
      exclude: /node_modules/,
      use: [{
        loader: "babel-loader"
      }]
    },{
      test: /\.less$/,
      use: extractLess.extract({
        fallback: "style-loader",
        use: [{
          loader: "css-loader",
          options: {
            minimize: false, // true,
            url: false
          }
        },{
          loader: "less-loader",
          options: {
            relativeUrls: true
          }
        }]
      })
    }]
  },
  resolve: {
    extensions: [".ts", ".tsx", ".js"]
  },
  externals: {
    "jquery": "jQuery",
    "react": "React",
    "react-dom": "ReactDOM"
  },
  entry: {
    /*'core-js/modules/es6.promise', */
    'ucms_dashboard': [
      /* 'babel-polyfill', */
      SRC_DASHBOARD + '/seven-fixes.less'
    ]
  },
  output: {
    filename: '[name]/dist/script.js'
  }
};
