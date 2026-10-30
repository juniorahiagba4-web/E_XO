import localFont from "next/font/local";

// TeX Gyre Heros: a free (GUST Font License, see GUST-FONT-LICENSE.txt)
// redrawing of Helvetica. Real Helvetica only exists on Apple devices, so
// shipping this one gives every visitor — Android and Windows included — the
// same Helvetica look. The files are subset to Latin + French accents.
export const heros = localFont({
  src: [
    { path: "./texgyreheros-regular.woff2", weight: "400", style: "normal" },
    { path: "./texgyreheros-italic.woff2", weight: "400", style: "italic" },
    { path: "./texgyreheros-bold.woff2", weight: "700", style: "normal" },
    { path: "./texgyreheros-bolditalic.woff2", weight: "700", style: "italic" },
  ],
  variable: "--font-heros",
  display: "swap",
  fallback: ["Helvetica Neue", "Helvetica", "Arial", "sans-serif"],
});
