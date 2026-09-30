"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";
import { useAuth } from "@/lib/auth-context";
import { getMyReservations, ApiError } from "@/lib/api";
import { formatPrice } from "@/lib/format";
import type { Reservation } from "@/lib/types";

export default function AccountPage() {
  const t = useTranslations("account");
  const tStatus = useTranslations("reservationStatus");
  const locale = useLocale();
  const router = useRouter();
  const { user, token, loading } = useAuth();

  const [reservations, setReservations] = useState<Reservation[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (loading) return;
    if (!user || !token) {
      router.push("/connexion?redirect=/compte");
      return;
    }
    getMyReservations(locale, token)
      .then(setReservations)
      .catch((err) => setError(err instanceof ApiError ? err.message : t("error")));
  }, [loading, user, token, locale, router, t]);

  if (loading || !user) {
    return <div className="mx-auto max-w-3xl px-4 py-16 text-slate-500">{t("loading")}</div>;
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-2 text-3xl font-bold text-brand-navy">{t("title")}</h1>
      <p className="mb-8 text-slate-500">
        {t("greeting", { name: user.first_name ?? user.name })}
      </p>

      {error && <p className="text-sm text-red-600">{error}</p>}

      {reservations && reservations.length === 0 && (
        <p className="text-slate-500">{t("empty")}</p>
      )}

      {reservations && reservations.length > 0 && (
        <div className="divide-y divide-slate-200 rounded-2xl border border-slate-200">
          {reservations.map((res) => (
            <div key={res.reference} className="flex flex-wrap items-center justify-between gap-3 p-4">
              <div>
                <p className="font-mono text-sm font-medium text-brand-navy">{res.reference}</p>
                <p className="text-xs text-slate-500">
                  {res.type === "purchase" ? t("typePurchase") : t("typeRental")} · {tStatus(res.status)}
                </p>
              </div>
              <div className="flex items-center gap-4">
                <span className="font-semibold text-brand-navy">
                  {formatPrice(res.total)} {res.currency}
                </span>
                {res.quote_pdf_url && (
                  <a
                    href={res.quote_pdf_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-sm text-brand-gold-dark hover:underline"
                  >
                    {t("viewPdf")}
                  </a>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
