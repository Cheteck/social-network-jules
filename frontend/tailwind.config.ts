import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['var(--font-geist-sans)'],
        mono: ['var(--font-geist-mono)'],
      },
      colors: {
        // S'inspirer du thème sombre de Twitter/X
        // Ces couleurs peuvent être ajustées plus tard
        'x-bg': '#000000', // Noir profond pour le fond principal
        'x-card-bg': '#15181C', // Fond des cartes/tweets légèrement plus clair
        'x-border': '#2F3336', // Couleur des bordures
        'x-primary-text': '#E7E9EA', // Texte principal (blanc cassé)
        'x-secondary-text': '#71767B', // Texte secondaire (gris)
        'x-accent': '#1D9BF0', // Couleur d'accentuation (bleu Twitter)
        'x-accent-hover': '#1A8CD8', // Hover pour l'accent
      },
    },
  },
  plugins: [],
};
export default config;
