"use client";

import { useEffect } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import ErrorState from "@/components/ErrorState";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  const t = useTranslations("errors");

  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <>
      <Header />
      <main className="flex-1">
        <ErrorState
          code="500"
          title={t("genericTitle")}
          message={t("genericMessage")}
          actions={
            <>
              <button
                type="button"
                onClick={reset}
                className="rounded-full bg-brand-navy-light px-6 py-3 font-medium text-white transition hover:bg-brand-navy"
              >
                {t("retry")}
              </button>
              <Link
                href="/"
                className="rounded-full border border-brand-navy-light px-6 py-3 font-medium text-brand-navy-light transition hover:bg-brand-navy-light hover:text-white"
              >
                {t("backHome")}
              </Link>
            </>
          }
        />
      </main>
      <Footer />
    </>
  );
}
