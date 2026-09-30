"use client";

import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getReservation, ApiError } from "@/lib/api";
import { buildWhatsappLink } from "@/lib/config";
import { formatPrice } from "@/lib/format";
import type { Reservation } from "@/lib/types";

export default function TrackQuotePage() {
  const t = useTranslations("quoteConfirmation");
  const tStatus = useTranslations("reservationStatus");
  const locale = useLocale();
  const [reference, setReference] = useState("");
  const [email, setEmail] = useState("");
  const [reservation, setReservation] = useState<Reservation | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setReservation(null);
    try {
      const result = await getReservation(locale, reference.trim(), email.trim());
      setReservation(result);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Introuvable");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto max-w-xl px-4 py-16">
      <h1 className="mb-2 text-3xl font-bold text-brand-navy">Suivre mon devis</h1>
      <p className="mb-6 text-sm text-slate-500">
        Renseignez la référence de votre devis ainsi que l&apos;email utilisé lors de la demande.
      </p>
      <form onSubmit={handleSubmit} className="flex flex-col gap-3">
        <input
          required
          placeholder="Ex: RES-2026-00042"
          value={reference}
          onChange={(e) => setReference(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />
        <input
          required
          type="email"
          placeholder="Email utilisé pour la demande"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
        />
        <button
          type="submit"
          disabled={loading}
          className="rounded-full bg-brand-navy px-6 py-2 font-medium text-white hover:bg-brand-navy-light disabled:opacity-50"
        >
          Rechercher
        </button>
      </form>

      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}

      {reservation && (
        <div className="mt-8 rounded-2xl border border-slate-200 p-6">
          <p className="text-sm text-slate-500">
            {t("reference")}: <span className="font-mono">{reservation.reference}</span>
          </p>
          <p className="mt-2 font-medium text-brand-navy">Statut : {tStatus(reservation.status)}</p>
          <p className="mt-1 text-slate-600">
            Total : {formatPrice(reservation.total)} {reservation.currency}
          </p>
          <div className="mt-4 flex flex-col gap-2 sm:flex-row">
            {reservation.quote_pdf_url && (
              <a
                href={reservation.quote_pdf_url}
                target="_blank"
                rel="noopener noreferrer"
                className="rounded-full bg-brand-navy px-5 py-2 text-center text-sm font-medium text-white"
              >
                {t("downloadPdf")}
              </a>
            )}
            <a
              href={buildWhatsappLink(reservation.whatsapp_message)}
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-full bg-[#25D366] px-5 py-2 text-center text-sm font-medium text-white"
            >
              {t("whatsappCta")}
            </a>
          </div>
        </div>
      )}
    </div>
  );
}
