"use client";

import { usePathname } from "next/navigation";

const COPY = {
  fr: {
    title: "Page introuvable",
    message: "La page que vous cherchez n'existe pas ou a été déplacée.",
    cta: "Retour à l'accueil",
  },
  en: {
    title: "Page not found",
    message: "The page you're looking for doesn't exist or has moved.",
    cta: "Back to home",
  },
};

export default function RootNotFound() {
  const pathname = usePathname();
  const locale = pathname?.startsWith("/en") ? "en" : "fr";
  const t = COPY[locale];
  const homeHref = `/${locale}`;

  return (
    <html lang={locale}>
      <body
        style={{
          margin: 0,
          minHeight: "100vh",
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          justifyContent: "center",
          fontFamily: "system-ui, sans-serif",
          background: "#ffffff",
          color: "#0b1045",
        }}
      >
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src="/logo.png" alt="La Perle d'Or" width={64} height={64} style={{ borderRadius: 12, marginBottom: 24 }} />
        <span style={{ fontSize: "56px", fontWeight: 900, color: "#f0b429", lineHeight: 1 }}>404</span>
        <h1 style={{ fontSize: "22px", fontWeight: 700, margin: "12px 0 8px" }}>{t.title}</h1>
        <p style={{ color: "#64748b", marginBottom: "24px", textAlign: "center", maxWidth: 360 }}>{t.message}</p>
        <a
          href={homeHref}
          style={{
            borderRadius: "9999px",
            background: "#0b1045",
            color: "#ffffff",
            padding: "12px 24px",
            fontWeight: 500,
            textDecoration: "none",
          }}
        >
          {t.cta}
        </a>
      </body>
    </html>
  );
}
