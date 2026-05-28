/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        // ZoyicaTheme views (package source + published)
        "./src/Resources/**/*.blade.php",
        "./src/Resources/**/*.js",
        "../../../resources/themes/zoyicatheme/**/*.blade.php",

        // Shop package views — must be included so no shop CSS classes get dropped
        "../../Webkul/Shop/src/Resources/**/*.blade.php",
        "../../Webkul/Shop/src/Resources/**/*.js",
    ],

    theme: {
        container: {
            center: true,
            screens: { "2xl": "1440px" },
            padding: { DEFAULT: "90px" },
        },

        // Mirror the Shop's custom breakpoints exactly
        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1440px",
            1180: "1180px",
            1060: "1060px",
            991: "991px",
            868: "868px",
        },

        extend: {
            // Mirror the Shop's custom colors exactly
            colors: {
                navyBlue:    "#060C3B",
                lightOrange: "#F6F2EB",
                darkGreen:   "#40994A",
                darkBlue:    "#0044F2",
                darkPink:    "#F85156",
            },

            fontFamily: {
                poppins:  ["Poppins", "sans-serif"],
                dmserif:  ["DM Serif Display", "serif"],
            },
        },
    },

    plugins: [],

    safelist: [{ pattern: /icon-/ }],
};
