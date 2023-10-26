const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const fs = require('fs');

const generateEntries = () => {
  const viewsPath = path.join(__dirname, 'src', 'Core/Views/React');
  const entry = {};

  fs.readdirSync(viewsPath).forEach((file) => {
    if (file.endsWith('.tsx')) {
      const name = file.replace(/\.tsx$/, '');
      entry[name] = path.join(viewsPath, file);
    }
  });

  return entry;
};

module.exports = {
  //entry: generateEntries(),
  entry: './src/index.tsx',
  output: {
    path: path.resolve(__dirname, 'src/Assets/Js'),
    filename: 'react-bundle.js',
  },
  module: {
    rules: [
      {
	test: /\.(js|mjs|jsx|ts|tsx)$/,
        exclude: /node_modules/,
        use: 'babel-loader',
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.jsx', '.ts', '.tsx'],	
  }
 };
