const path = require('path');
const fs = require('fs');

const generateEntries = () => {
  const viewsPath = path.join(__dirname, 'src/Components');
  const entry = {};

  fs.readdirSync(viewsPath).forEach((file) => {
    if (file.endsWith('.tsx')) {
      const name = file.replace(/\.tsx$/, '');
      entry[name] = path.join(viewsPath, file);
    }
  });

  return entry;
};

module.exports = (env, arg) => {
  // trying render just one specific component
  // npm run build:component -- component=Table
  let entry = arg.env.component != undefined 
    ? './src/Components/' + arg.env.component + '.tsx': './src/Components/index.tsx'; 

  return {
    //entry: generateEntries(),
    entry: entry, 
    output: {
      path: path.resolve(__dirname, 'src/Assets/Js/React'),
      filename: '[fullhash].js',
      clean: true
      //filename: 'react-bundle.js'
      //path: path.resolve(__dirname, 'src/Assets/Js/Components'),
     //filename: '[name].js',
     //libraryTarget: 'umd',
     //library: '[name]'
    },
    optimization: {
      minimize: false, // Disable minification
    },
    module: {
      rules: [
        {
          test: /\.(js|mjs|jsx|ts|tsx)$/,
          exclude: /node_modules/,
          use: 'babel-loader',
        },
        {
          test: /\.(scss|css)$/,
          use: ['style-loader', 'css-loader', 'sass-loader'],
        }
      ],
    },
    resolve: {
      extensions: ['.js', '.jsx', '.ts', '.tsx', '.scss', '.css'],
    }
  }
};
