export default {
  title: 'Craft Image Guru',
  description: 'Stylish off-server image transforms #craftcms projects',
  themeConfig: {
    logo: '/zaengle.svg',
    nav: [
      { text: 'Guide', link: '/' },
      { text: 'GitHub', link: 'https://github.com/zaengle/craft-imageguru' },
      { text: 'Open an issue', link: 'https://github.com/zaengle/craft-imageguru/issues' },
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Home', link: '/' },
          { text: 'Installation', link: '/01-installation' },
        ]
      },{
        text: 'Usage',
        items: [
          { text: 'Configuration', link: '/02-config' },
          { text: 'Bundled Transformers', link: '/03-bundled-transformers' },
          { text: 'Writing a Custom Transformer', link: '/04-adding-transformers' },
        ]
      },
      {
        text: 'Made with ❤️ by Zaengle',
        items: [
          { text: 'Be Nice, Do Good', link: 'https://zaengle.com/'},
          { text: 'MIT Licensed', link: 'https://mit-license.org/'},
        ],
      }
    ]
  }
};
