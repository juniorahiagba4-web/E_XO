"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link, usePathname } from "@/i18n/navigation";
import LocaleSwitcher from "./LocaleSwitcher";
import CartPopup from "./CartPopup";
import UserMenu from "./UserMenu";
import BackButton from "./BackButton";
import Logo from "./Logo";

const NAV_LINKS = [
  { href: "/", key: "home" },
  { href: "/catalogue", key: "catalogue" },
  { href: "/contact", key: "contact" },
  { href: "/faq", key: "faq" },
  { href: "/suivi", key: "trackQuote" },
] as const;

function NavLink({ href, children, onClick }: { href: string; children: React.ReactNode; onClick?: () => void }) {
  const pathname = usePathname();
  const isActive = pathname === href;

  return (
    <Link
      href={href}
      onClick={onClick}
      className={`relative py-1 transition after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:rounded-full after:bg-brand-gold after:transition-all after:duration-200 ${
        isActive
          ? "text-brand-gold after:w-full"
          : "text-slate-200 after:w-0 hover:text-brand-gold hover:after:w-full"
      }`}
    >
      {children}
    </Link>
  );
}

export default function Header() {
  const t = useTranslations("nav");
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <header className="sticky top-0 z-40 bg-brand-navy">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <div className="flex items-center gap-2">
          <BackButton />
          <Link href="/">
            <Logo />
          </Link>
        </div>
        <nav className="hidden items-center gap-6 text-sm font-medium md:flex">
          {NAV_LINKS.map(({ href, key }) => (
            <NavLink key={href} href={href}>
              {t(key)}
            </NavLink>
          ))}
        </nav>
        <div className="flex items-center gap-3">
          <CartPopup />
          <UserMenu />
          <LocaleSwitcher />
          <button
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label={t("menu")}
            className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-slate-200 transition hover:border-brand-gold hover:text-brand-gold md:hidden"
          >
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
              <path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 md:hidden">
          <div
            className="absolute inset-0 bg-brand-navy/40 backdrop-blur-sm"
            style={{ animation: "backdrop-in .15s ease-out" }}
            onClick={() => setMobileOpen(false)}
          />
          <div
            className="absolute inset-y-0 right-0 flex w-full max-w-xs flex-col bg-brand-navy p-6 shadow-2xl"
            style={{ animation: "modal-in .18s ease-out" }}
          >
            <div className="mb-8 flex items-center justify-between">
              <Logo />
              <button
                type="button"
                onClick={() => setMobileOpen(false)}
                aria-label={t("close")}
                className="flex h-9 w-9 items-center justify-center rounded-full text-slate-200 hover:bg-white/10"
              >
                ✕
              </button>
            </div>
            <nav className="flex flex-col gap-5 text-base font-medium">
              {NAV_LINKS.map(({ href, key }) => (
                <NavLink key={href} href={href} onClick={() => setMobileOpen(false)}>
                  {t(key)}
                </NavLink>
              ))}
            </nav>
          </div>
        </div>
      )}
    </header>
  );
}
