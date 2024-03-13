module.exports = {
  extends: [
    '@nextcloud',
  ],
  // some unused toolkit files
  ignorePatterns: [
    'src/toolkit/util/file-node-helper.js',
    'src/toolkit/util/file-download.js',
    'src/toolkit/util/dialogs.js',
    'src/toolkit/util/ajax.js',
  ],
  rules: {
    'no-tabs': ['error', { allowIndentationTabs: false }],
    indent: ['error', 2],
    'no-mixed-spaces-and-tabs': 'error',
    'vue/html-indent': ['error', 2],
    semi: ['error', 'always'],
    'no-console': 'off',
    'n/no-missing-require': [
      'error', {
        resolvePaths: [
          './src',
          './style',
          './',
        ],
        tryExtensions: ['.js', '.json', '.node', '.css', '.scss', '.xml', '.vue'],
      },
    ],
    // Do allow line-break before closing brackets
    'vue/html-closing-bracket-newline': ['error', { singleline: 'never', multiline: 'always' }],
    'n/no-unpublished-import': 'off',
    'n/no-unpublished-require': 'off',
  },
  overrides: [
    {
      files: ['*.vue'],
      rules: {
        semi: ['error', 'never'],
      },
    },
  ],
};
