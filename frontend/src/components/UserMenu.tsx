"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { useAuth } from "@/lib/auth-context";

export default function UserMenu() {
  const t = useTranslations("auth");
  const { user, logout } = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);

  if (!user) {
    return (
      <>
        {/* Compact icon on phones: the full "Connexion" pill would eat the
            space the logo needs in a narrow header. */}
        <Link
          href="/connexion"
          aria-label={t("login")}
          title={t("login")}
          className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-slate-200 transition hover:border-brand-gold hover:text-brand-gold md:hidden"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
            <circle cx="12" cy="8" r="3.4" />
            <path strokeLinecap="round" d="M4.8 19.5c1.1-3.1 3.8-4.8 7.2-4.8s6.1 1.7 7.2 4.8" />
          </svg>
        </Link>
        <Link
          href="/connexion"
          className="hidden rounded-full border border-white/20 px-4 py-1.5 text-sm font-medium text-slate-200 transition hover:border-brand-gold hover:text-brand-gold md:inline-block"
        >
          {t("login")}
        </Link>
      </>
    );
  }

  async function handleLogout() {
    setOpen(false);
    await logout();
    router.push("/");
  }

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-gold text-sm font-semibold text-brand-navy"
        aria-label={t("myAccount")}
      >
        {(user.first_name?.[0] ?? user.name[0]).toUpperCase()}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
          <div className="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
            <p className="truncate px-4 py-2 text-xs text-slate-400">{user.email}</p>
            <Link
              href="/compte"
              onClick={() => setOpen(false)}
              className="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
            >
              {t("myOrders")}
            </Link>
            <Link
              href="/favoris"
              onClick={() => setOpen(false)}
              className="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
            >
              {t("myFavorites")}
            </Link>
            <button
              type="button"
              onClick={handleLogout}
              className="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-slate-50"
            >
              {t("logout")}
            </button>
          </div>
        </>
      )}
    </div>
  );
}
