/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/**/*.{js,jsx,ts,tsx}",
    "./components/**/*.{js,jsx,ts,tsx}",
  ],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        primary: "#005bbf",
        "primary-container": "#1a73e8",
        "on-primary": "#ffffff",
        "on-primary-container": "#ffffff",
        secondary: "#006e2c",
        "on-secondary": "#ffffff",
        tertiary: "#9e4300",
        error: "#ba1a1a",
        background: "#f8f9fa",
        "on-background": "#1b1c1e",
        surface: "#f8f9fa",
        "surface-variant": "#e1e3e4",
        "surface-container": "#eef0f1",
        "surface-container-high": "#e7e8e9",
        "surface-container-highest": "#dfe0e1",
        "surface-container-lowest": "#ffffff",
        "on-surface": "#1b1c1e",
        "on-surface-variant": "#414754",
        outline: "#727785",
        "outline-variant": "#c1c6d6",
      },
      fontFamily: {
        sans: ["AtkinsonHyperlegible_400Regular"],
        bold: ["AtkinsonHyperlegible_700Bold"],
      },
    },
  },
  plugins: [],
};
