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
      <Link
        href="/connexion"
        className="rounded-full border border-white/20 px-4 py-1.5 text-sm font-medium text-slate-200 transition hover:border-brand-gold hover:text-brand-gold"
      >
        {t("login")}
      </Link>
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
