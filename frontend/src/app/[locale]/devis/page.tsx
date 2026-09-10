"use client";

import { useMemo, useState } from "react";
import { differenceInCalendarDays } from "date-fns";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import { createReservation, ApiError } from "@/lib/api";
import { buildWhatsappLink } from "@/lib/config";
import type { Reservation } from "@/lib/types";

export default function DevisPage() {
  const t = useTranslations("quoteForm");
  const tConfirm = useTranslations("quoteConfirmation");
  const tProduct = useTranslations("product");
  const locale = useLocale();
  const cart = useCart();

  const [deliveryMethod, setDeliveryMethod] = useState<"delivery" | "pickup">("pickup");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [address, setAddress] = useState("");
  const [notes, setNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reservation, setReservation] = useState<Reservation | null>(null);

  const nights = useMemo(() => {
    if (!cart.eventStartDate || !cart.eventEndDate) return 0;
    return Math.max(
      1,
      differenceInCalendarDays(new Date(cart.eventEndDate), new Date(cart.eventStartDate)),
    );
  }, [cart.eventStartDate, cart.eventEndDate]);

  const total = cart.lines.reduce(
    (sum, line) => sum + Number(line.item.rental_price_per_day) * line.quantity * nights,
    0,
  );

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (cart.lines.length === 0) return;
    setSubmitting(true);
    setError(null);
    try {
      const result = await createReservation(locale, {
        event_start_date: cart.eventStartDate,
        event_end_date: cart.eventEndDate,
        delivery_method: deliveryMethod,
        delivery_address: deliveryMethod === "delivery" ? address : undefined,
        notes,
        customer: { first_name: firstName, last_name: lastName, email, phone },
        items: cart.lines.map((l) => ({ item_id: l.item.id, quantity: l.quantity })),
      });
      setReservation(result);
      cart.clear();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  if (reservation) {
    return (
      <div className="mx-auto max-w-xl px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-slate-900">{tConfirm("title")}</h1>
        <p className="mt-2 text-slate-500">
          {tConfirm("reference")}: <span className="font-mono font-semibold">{reservation.reference}</span>
        </p>
        <div className="mt-8 flex flex-col items-center gap-3">
          {reservation.quote_pdf_url && (
            <a
              href={reservation.quote_pdf_url}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full max-w-xs rounded-full bg-slate-900 px-6 py-3 text-center font-medium text-white hover:bg-slate-700"
            >
              {tConfirm("downloadPdf")}
            </a>
          )}
          <a
            href={buildWhatsappLink(reservation.whatsapp_message)}
            target="_blank"
            rel="noopener noreferrer"
            className="w-full max-w-xs rounded-full bg-[#25D366] px-6 py-3 text-center font-medium text-white hover:brightness-95"
          >
            {tConfirm("whatsappCta")}
          </a>
        </div>
        <p className="mt-8 text-sm text-slate-400">{tConfirm("trackInfo")}</p>
      </div>
    );
  }

  if (cart.lines.length === 0) {
    return (
      <div className="mx-auto max-w-xl px-4 py-16 text-center">
        <p className="text-slate-500">{t("emptyCart")}</p>
        <Link href="/catalogue" className="mt-4 inline-block text-amber-600 hover:underline">
          &larr; {tProduct("backToCatalogue")}
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-8 text-3xl font-bold text-slate-900">{t("title")}</h1>

      <div className="mb-8 divide-y divide-slate-200 rounded-2xl border border-slate-200">
        {cart.lines.map((line) => (
          <div key={line.item.id} className="flex items-center justify-between gap-4 p-4">
            <div>
              <p className="font-medium text-slate-900">{line.item.name}</p>
              <p className="text-sm text-slate-500">
                {line.item.rental_price_per_day} x {line.quantity} x {nights}j
              </p>
            </div>
            <div className="flex items-center gap-3">
              <input
                type="number"
                min={1}
                max={line.item.total_stock}
                value={line.quantity}
                onChange={(e) => cart.updateQuantity(line.item.id, Number(e.target.value))}
                className="w-16 rounded-lg border border-slate-300 px-2 py-1 text-sm"
              />
              <button
                type="button"
                onClick={() => cart.removeLine(line.item.id)}
                className="text-sm text-red-500 hover:underline"
              >
                ✕
              </button>
            </div>
          </div>
        ))}
        <div className="flex items-center justify-between p-4 font-semibold text-slate-900">
          <span>Total estimé</span>
          <span>{total.toLocaleString()} XOF</span>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="flex flex-col gap-6">
        <fieldset className="grid gap-4 sm:grid-cols-2">
          <legend className="mb-2 font-semibold text-slate-900">{t("customerInfo")}</legend>
          <input required placeholder={t("firstName")} value={firstName} onChange={(e) => setFirstName(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2" />
          <input required placeholder={t("lastName")} value={lastName} onChange={(e) => setLastName(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2" />
          <input required placeholder={t("phone")} value={phone} onChange={(e) => setPhone(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2" />
          <input required type="email" placeholder={t("email")} value={email} onChange={(e) => setEmail(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2" />
        </fieldset>

        <fieldset>
          <legend className="mb-2 font-semibold text-slate-900">{t("deliveryMethod")}</legend>
          <div className="flex gap-6">
            <label className="flex items-center gap-2 text-sm">
              <input type="radio" checked={deliveryMethod === "pickup"} onChange={() => setDeliveryMethod("pickup")} />
              {t("pickup")}
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="radio" checked={deliveryMethod === "delivery"} onChange={() => setDeliveryMethod("delivery")} />
              {t("delivery")}
            </label>
          </div>
          {deliveryMethod === "delivery" && (
            <input
              required
              placeholder={t("address")}
              value={address}
              onChange={(e) => setAddress(e.target.value)}
              className="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          )}
        </fieldset>

        <textarea
          placeholder={t("notes")}
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          className="rounded-lg border border-slate-300 px-3 py-2"
          rows={3}
        />

        {error && <p className="text-sm text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="rounded-full bg-slate-900 px-6 py-3 font-medium text-white transition hover:bg-slate-700 disabled:opacity-50"
        >
          {submitting ? t("submitting") : t("submit")}
        </button>
      </form>
    </div>
  );
}
