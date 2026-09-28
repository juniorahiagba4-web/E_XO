"use client";

import { useEffect } from "react";

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <html lang="fr">
      <body
        style={{
          margin: 0,
          minHeight: "100vh",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          fontFamily: "'Helvetica Neue', Helvetica, Arial, sans-serif",
          background: "#ffffff",
          color: "#0b1045",
        }}
      >
        <div style={{ textAlign: "center", padding: "24px", maxWidth: "420px" }}>
          <h1 style={{ fontSize: "22px", fontWeight: 700, marginBottom: "8px" }}>
            Le site rencontre un problème
          </h1>
          <p style={{ color: "#64748b", marginBottom: "24px" }}>
            Une erreur inattendue est survenue. Merci de réessayer dans quelques instants.
          </p>
          <button
            type="button"
            onClick={reset}
            style={{
              borderRadius: "9999px",
              background: "#0b1045",
              color: "#ffffff",
              padding: "12px 24px",
              fontWeight: 500,
              border: "none",
              cursor: "pointer",
            }}
          >
            Réessayer
          </button>
        </div>
      </body>
    </html>
  );
}
