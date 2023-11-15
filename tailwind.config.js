/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './Views/*.php',
    './static/js/*.js',
    './node_modules/flowbite/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        'asparagus': {
          '50': '#f4f7ee',
          '100': '#e7eed9',
          '200': '#d2dfb7',
          '300': '#b4c98d',
          '400': '#98b368',
          '500': '#799649',
          '600': '#5f7838',
          '700': '#4a5d2e',
          '800': '#3d4b29',
          '900': '#364126',
          '950': '#1a2211',
        },
        'japonica': {
          '50': '#fdf5f3',
          '100': '#fbe9e5',
          '200': '#f8d7d0',
          '300': '#f2bbaf',
          '400': '#e89481',
          '500': '#de7b65',
          '600': '#c7543b',
          '700': '#a7442e',
          '800': '#8a3b2a',
          '900': '#743628',
          '950': '#3e1911',
        },

      },
    },
  },
  plugins: [
    require('flowbite/plugin'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/aspect-ratio'),
  ],
}