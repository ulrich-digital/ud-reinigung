// plugins/ud-reinigung/webpack.config.js
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Falls defaultConfig eine Funktion ist, aufrufen; sonst direkt verwenden
const asFn = (cfg) => (typeof cfg === 'function' ? cfg : () => cfg);

module.exports = (env, argv) => {
  const config = asFn(defaultConfig)(env, argv);

  // ➕ Bestehende Entries beibehalten und unsere hinzufügen
  config.entry = {
    ...(config.entry || {}),
    frontend: path.resolve(__dirname, 'src/js/frontend.js'),
    'reinigung-editor': path.resolve(__dirname, 'src/js/reinigung-editor.js'),
  };

  // Ausgabeordner/Dateinamen
  config.output = {
    ...config.output,
    filename: '[name].js',
    path: path.resolve(__dirname, 'build'),
  };

  return config;
};
