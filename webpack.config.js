const CleanWebpackPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const MinifyPlugin = require("babel-minify-webpack-plugin");
const path = require('path');
const webpack = require('webpack');

module.exports = (env, argv) => {
  const SRC_DASHBOARD = path.resolve(__dirname, 'ucms_dashboard/src-front');
  const SRC_TREE = path.resolve(__dirname, 'ucms_tree/src-front');

  const plugins = [
    new CleanWebpackPlugin([
      path.resolve(__dirname, 'polyfill/dist'),
      path.resolve(__dirname, 'ucms_dashboard/dist'),
      path.resolve(__dirname, 'ucms_tree/dist')
    ]),
    new MiniCssExtractPlugin({filename: "[name]/dist/style.css"})
  ];

  if ('production' === argv.mode) {
    plugins.push(new MinifyPlugin());
  }

  return {
    devtool: ('production' === argv.mode ? false : 'source-map'),
    plugins: plugins,
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
        use: [
          MiniCssExtractPlugin.loader,
          "css-loader",
          "less-loader"
        ]
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
      /* polyfill: [
        'core-js/modules/es6.promise',
      ], */
      'ucms_dashboard': [
        SRC_DASHBOARD + '/seven-fixes.less'
      ],
      'ucms_tree': [
        SRC_TREE + '/index.ts',
        SRC_TREE + '/tree.less'
      ]
    },
    output: {
      path: __dirname,
      filename: '[name]/dist/script.js'
    }
  };
};
