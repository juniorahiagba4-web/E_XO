import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import Logo from "@/components/Logo";

export default async function AuthLayoutWrapper({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "auth" });

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <div className="flex items-center justify-between bg-brand-navy px-6 py-4">
        <Link href="/">
          <Logo size={32} withWordmark={false} />
        </Link>
        <Link
          href="/"
          className="flex items-center gap-1.5 text-sm font-medium text-slate-200 hover:text-brand-gold"
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 18l-6-6 6-6" />
          </svg>
          {t("backToShop")}
        </Link>
      </div>
      <div className="flex flex-1">{children}</div>
    </div>
  );
}
