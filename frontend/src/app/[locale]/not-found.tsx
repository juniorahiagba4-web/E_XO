"use client";

import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import ErrorState from "@/components/ErrorState";

export default function NotFound() {
  const t = useTranslations("errors");

  return (
    <>
      <Header />
      <main className="flex-1">
        <ErrorState
          code="404"
          title={t("notFoundTitle")}
          message={t("notFoundMessage")}
          actions={
            <>
              <Link
                href="/"
                className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light"
              >
                {t("notFoundCta")}
              </Link>
              <Link
                href="/catalogue"
                className="rounded-full border border-brand-navy px-6 py-3 font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white"
              >
                {t("notFoundCatalogue")}
              </Link>
            </>
          }
        />
      </main>
      <Footer />
    </>
  );
}
