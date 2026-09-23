"use client";

import { useLocale } from "next-intl";
import { usePathname, useRouter } from "@/i18n/navigation";
import { routing } from "@/i18n/routing";

export default function LocaleSwitcher() {
  const locale = useLocale();
  const pathname = usePathname();
  const router = useRouter();

  return (
    <div className="flex items-center gap-1 text-sm">
      {routing.locales.map((loc) => (
        <button
          key={loc}
          onClick={() => router.replace(pathname, { locale: loc })}
          className={`rounded px-2 py-1 uppercase transition ${
            loc === locale
              ? "bg-brand-gold text-brand-navy"
              : "text-slate-300 hover:bg-white/10"
          }`}
        >
          {loc}
        </button>
      ))}
    </div>
  );
}
