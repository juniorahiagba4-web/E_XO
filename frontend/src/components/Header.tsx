import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import LocaleSwitcher from "./LocaleSwitcher";
import CartBadge from "./CartBadge";

export default function Header() {
  const t = useTranslations("nav");

  return (
    <header className="border-b border-slate-200 bg-white/80 backdrop-blur sticky top-0 z-40">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <Link href="/" className="text-lg font-bold tracking-tight text-slate-900">
          EventLoc<span className="text-amber-500">.</span>
        </Link>
        <nav className="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
          <Link href="/" className="hover:text-slate-900">
            {t("home")}
          </Link>
          <Link href="/catalogue" className="hover:text-slate-900">
            {t("catalogue")}
          </Link>
          <Link href="/suivi" className="hover:text-slate-900">
            {t("trackQuote")}
          </Link>
        </nav>
        <div className="flex items-center gap-3">
          <CartBadge />
          <LocaleSwitcher />
        </div>
      </div>
    </header>
  );
}
