"use client";

import { useEffect, useState } from "react";

const COOKIE_CONSENT_KEY = "lpd_cookie_consent";

export default function CookieBanner() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    try {
      const consent = localStorage.getItem(COOKIE_CONSENT_KEY);
      if (!consent) {
        setVisible(true);
      }
    } catch {
      setVisible(true);
    }
  }, []);

  const accept = () => {
    try {
      localStorage.setItem(COOKIE_CONSENT_KEY, "accepted");
    } catch {
      // ignore
    }
    setVisible(false);
  };

  const decline = () => {
    try {
      localStorage.setItem(COOKIE_CONSENT_KEY, "declined");
    } catch {
      // ignore
    }
    setVisible(false);
  };

  if (!visible) return null;

  return (
    <div className="fixed inset-x-0 bottom-0 z-[60] px-4 pb-4">
      <div className="mx-auto flex max-w-6xl flex-col gap-3 rounded-lg bg-brand-navy/95 p-4 text-sm text-slate-100 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <p className="text-slate-200">
          Ce site utilise des cookies pour améliorer votre expérience. En continuant, vous acceptez leur utilisation.
        </p>
        <div className="flex shrink-0 items-center gap-2">
          <button
            type="button"
            onClick={decline}
            className="rounded-md border border-white/30 px-3 py-1.5 text-slate-200 transition hover:border-white/60"
          >
            Refuser
          </button>
          <button
            type="button"
            onClick={accept}
            className="rounded-md bg-brand-gold px-3 py-1.5 font-medium text-brand-navy transition hover:bg-brand-gold/90"
          >
            Accepter
          </button>
        </div>
      </div>
    </div>
  );
}
