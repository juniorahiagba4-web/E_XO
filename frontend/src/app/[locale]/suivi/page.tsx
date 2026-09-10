"use client";

import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getReservation, ApiError } from "@/lib/api";
import { buildWhatsappLink } from "@/lib/config";
import type { Reservation } from "@/lib/types";

export default function TrackQuotePage() {
  const t = useTranslations("quoteConfirmation");
  const locale = useLocale();
  const [reference, setReference] = useState("");
  const [reservation, setReservation] = useState<Reservation | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setReservation(null);
    try {
      const result = await getReservation(locale, reference.trim());
      setReservation(result);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Introuvable");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto max-w-xl px-4 py-16">
      <h1 className="mb-6 text-2xl font-bold text-slate-900">Suivre mon devis</h1>
      <form onSubmit={handleSubmit} className="flex gap-2">
        <input
          required
          placeholder="Ex: RES-2026-00042"
          value={reference}
          onChange={(e) => setReference(e.target.value)}
          className="flex-1 rounded-lg border border-slate-300 px-3 py-2"
        />
        <button
          type="submit"
          disabled={loading}
          className="rounded-full bg-slate-900 px-6 py-2 font-medium text-white hover:bg-slate-700 disabled:opacity-50"
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
          <p className="mt-2 font-medium text-slate-900">Statut : {reservation.status}</p>
          <p className="mt-1 text-slate-600">
            Total : {reservation.total} {reservation.currency}
          </p>
          <div className="mt-4 flex flex-col gap-2 sm:flex-row">
            {reservation.quote_pdf_url && (
              <a
                href={reservation.quote_pdf_url}
                target="_blank"
                rel="noopener noreferrer"
                className="rounded-full bg-slate-900 px-5 py-2 text-center text-sm font-medium text-white"
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
