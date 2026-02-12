const colors = require('./tailwind.colors.js').colors
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue'
  ],
  theme: {
    extend: {
      colors: colors
    }
  },
  plugins: []
}
