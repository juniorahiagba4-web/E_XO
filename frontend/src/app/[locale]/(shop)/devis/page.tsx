"use client";

import { useMemo, useState } from "react";
import { differenceInCalendarDays } from "date-fns";
import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import { useAuth } from "@/lib/auth-context";
import { createReservation, validatePromoCode, ApiError } from "@/lib/api";
import { computeDiscountPreview } from "@/lib/promo";
import { buildWhatsappLink } from "@/lib/config";
import QuantityStepper from "@/components/QuantityStepper";
import type { Reservation } from "@/lib/types";

export default function DevisPage() {
  const t = useTranslations("quoteForm");
  const tConfirm = useTranslations("quoteConfirmation");
  const tProduct = useTranslations("product");
  const tAuth = useTranslations("auth");
  const locale = useLocale();
  const router = useRouter();
  const cart = useCart();
  const { user, token } = useAuth();

  const [deliveryMethod, setDeliveryMethod] = useState<"delivery" | "pickup">("pickup");
  const [address, setAddress] = useState("");
  const [notes, setNotes] = useState("");
  const [guestFirstName, setGuestFirstName] = useState("");
  const [guestLastName, setGuestLastName] = useState("");
  const [guestEmail, setGuestEmail] = useState("");
  const [guestPhone, setGuestPhone] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reservation, setReservation] = useState<Reservation | null>(null);
  const [promoInput, setPromoInput] = useState("");
  const [promoStatus, setPromoStatus] = useState<"idle" | "checking" | "error">("idle");
  const [promoError, setPromoError] = useState<string | null>(null);

  const isPurchase = cart.orderType === "purchase";

  const nights = useMemo(() => {
    if (isPurchase || !cart.eventStartDate || !cart.eventEndDate) return 0;
    return Math.max(
      1,
      differenceInCalendarDays(new Date(cart.eventEndDate), new Date(cart.eventStartDate)),
    );
  }, [isPurchase, cart.eventStartDate, cart.eventEndDate]);

  const lineTotal = (line: (typeof cart.lines)[number]) => {
    const unitPrice = isPurchase ? Number(line.item.sale_price ?? 0) : Number(line.item.rental_price_per_day);
    return isPurchase ? unitPrice * line.quantity : unitPrice * line.quantity * nights;
  };

  const subtotal = cart.lines.reduce((sum, line) => sum + lineTotal(line), 0);
  const discount = computeDiscountPreview(cart.promo, cart.lines, lineTotal);
  const total = Math.max(0, subtotal - discount);

  async function applyPromoCode() {
    if (!promoInput.trim() || cart.lines.length === 0) return;
    setPromoStatus("checking");
    setPromoError(null);
    try {
      const result = await validatePromoCode(
        locale,
        promoInput.trim(),
        cart.lines.map((l) => l.item.id),
      );
      cart.applyPromo(promoInput.trim().toUpperCase(), result);
      setPromoInput("");
      setPromoStatus("idle");
    } catch (err) {
      setPromoStatus("error");
      setPromoError(err instanceof ApiError ? err.message : t("error"));
    }
  }

  function close() {
    router.push("/catalogue");
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (cart.lines.length === 0 || !cart.orderType) return;
    setSubmitting(true);
    setError(null);
    try {
      const result = await createReservation(
        locale,
        {
          type: cart.orderType,
          event_start_date: isPurchase ? undefined : cart.eventStartDate,
          event_end_date: isPurchase ? undefined : cart.eventEndDate,
          delivery_method: deliveryMethod,
          delivery_address: deliveryMethod === "delivery" ? address : undefined,
          notes,
          promo_code: cart.promo?.code,
          customer: user
            ? undefined
            : {
                first_name: guestFirstName,
                last_name: guestLastName,
                email: guestEmail,
                phone: guestPhone,
              },
          items: cart.lines.map((l) => ({ item_id: l.item.id, quantity: l.quantity })),
        },
        token,
      );
      setReservation(result);
      cart.clear();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-brand-navy/50 p-4 backdrop-blur-sm"
      style={{ animation: "backdrop-in .15s ease-out" }}
      onClick={close}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        style={{ animation: "modal-in .18s ease-out" }}
      >
        <div className="flex items-center justify-between border-b border-slate-100 p-5">
          <h1 className="text-lg font-semibold text-brand-navy">
            {reservation ? tConfirm("title") : isPurchase ? t("titlePurchase") : t("title")}
          </h1>
          <button
            type="button"
            onClick={close}
            aria-label={t("close")}
            className="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-brand-navy"
          >
            ✕
          </button>
        </div>

        <div className="overflow-y-auto p-6">
          {reservation ? (
            <div className="text-center">
              <p className="mt-2 text-slate-500">
                {tConfirm("reference")}: <span className="font-mono font-semibold text-brand-navy">{reservation.reference}</span>
              </p>
              <div className="mt-8 flex flex-col items-center gap-3">
                {reservation.quote_pdf_url && (
                  <a
                    href={reservation.quote_pdf_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="w-full max-w-xs rounded-full bg-brand-navy px-6 py-3 text-center font-medium text-white hover:bg-brand-navy-light"
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
          ) : cart.lines.length === 0 ? (
            <div className="py-8 text-center">
              <p className="text-slate-500">{t("emptyCart")}</p>
              <Link href="/catalogue" className="mt-4 inline-block text-brand-gold-dark hover:underline">
                &larr; {tProduct("backToCatalogue")}
              </Link>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
              <div className="divide-y divide-slate-100 rounded-xl border border-slate-200">
                {cart.lines.map((line) => {
                  const unitPrice = isPurchase
                    ? Number(line.item.sale_price ?? 0)
                    : Number(line.item.rental_price_per_day);
                  const rowTotal = lineTotal(line);

                  return (
                    <div key={line.item.id} className="flex items-center gap-3 p-3">
                      <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        {line.item.image_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={line.item.image_url} alt={line.item.name} className="h-full w-full object-cover" />
                        ) : (
                          <div className="flex h-full w-full items-center justify-center text-slate-300">
                            {line.item.name.charAt(0)}
                          </div>
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate font-medium text-brand-navy">{line.item.name}</p>
                        <p className="text-sm text-slate-500">
                          {isPurchase
                            ? `${unitPrice.toLocaleString()} XOF`
                            : `${unitPrice.toLocaleString()} XOF x ${nights}j`}
                        </p>
                      </div>
                      <QuantityStepper
                        value={line.quantity}
                        min={1}
                        max={isPurchase ? line.item.purchasable_quantity : line.item.total_stock}
                        onChange={(value) => cart.updateQuantity(line.item.id, value)}
                      />
                      <p className="w-24 shrink-0 text-right font-medium text-brand-navy">
                        {rowTotal.toLocaleString()} XOF
                      </p>
                      <button
                        type="button"
                        onClick={() => cart.removeLine(line.item.id)}
                        aria-label={t("removeLine")}
                        className="shrink-0 text-sm text-red-500 hover:underline"
                      >
                        ✕
                      </button>
                    </div>
                  );
                })}
                <div className="p-4">
                  {cart.promo ? (
                    <div className="mb-3 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                      <span>{t("promoCodeApplied", { code: cart.promo.code })}</span>
                      <button type="button" onClick={cart.removePromo} className="font-medium underline">
                        {t("promoCodeRemove")}
                      </button>
                    </div>
                  ) : (
                    <div className="mb-3">
                      <div className="flex gap-2">
                        <input
                          value={promoInput}
                          onChange={(e) => setPromoInput(e.target.value)}
                          placeholder={t("promoCodePlaceholder")}
                          className="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-gold focus:outline-none"
                        />
                        <button
                          type="button"
                          onClick={applyPromoCode}
                          disabled={promoStatus === "checking" || !promoInput.trim()}
                          className="shrink-0 rounded-lg border border-brand-navy px-4 py-2 text-sm font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white disabled:opacity-40"
                        >
                          {promoStatus === "checking" ? t("promoCodeApplying") : t("promoCodeApply")}
                        </button>
                      </div>
                      {promoError && <p className="mt-1.5 text-xs text-red-500">{promoError}</p>}
                    </div>
                  )}

                  <div className="flex items-center justify-between text-sm text-slate-500">
                    <span>{t("subtotal")}</span>
                    <span>{subtotal.toLocaleString()} XOF</span>
                  </div>
                  {discount > 0 && (
                    <div className="mt-1 flex items-center justify-between text-sm text-emerald-600">
                      <span>{t("discount")}</span>
                      <span>-{discount.toLocaleString()} XOF</span>
                    </div>
                  )}
                  <div className="mt-2 flex items-center justify-between text-lg font-semibold text-brand-navy">
                    <span>{t("total")}</span>
                    <span>{total.toLocaleString()} XOF</span>
                  </div>
                </div>
              </div>

              {!user && (
                <div className="rounded-xl bg-brand-gold/10 px-4 py-3 text-sm text-brand-navy">
                  {tAuth("guestIntro")}{" "}
                  <Link href="/connexion?redirect=/devis" className="font-medium underline">
                    {tAuth("login")}
                  </Link>
                </div>
              )}

              {!user && (
                <fieldset className="grid gap-4 sm:grid-cols-2">
                  <legend className="sr-only">{tAuth("continueAsGuest")}</legend>
                  <input
                    required
                    placeholder={tAuth("firstName")}
                    value={guestFirstName}
                    onChange={(e) => setGuestFirstName(e.target.value)}
                    className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                  />
                  <input
                    required
                    placeholder={tAuth("lastName")}
                    value={guestLastName}
                    onChange={(e) => setGuestLastName(e.target.value)}
                    className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                  />
                  <input
                    required
                    type="email"
                    placeholder={tAuth("email")}
                    value={guestEmail}
                    onChange={(e) => setGuestEmail(e.target.value)}
                    className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                  />
                  <input
                    required
                    placeholder={tAuth("phone")}
                    value={guestPhone}
                    onChange={(e) => setGuestPhone(e.target.value)}
                    className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                  />
                </fieldset>
              )}

              <fieldset>
                <legend className="mb-2 font-semibold text-brand-navy">{t("deliveryMethod")}</legend>
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
                    className="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                  />
                )}
              </fieldset>

              <textarea
                placeholder={t("notes")}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-gold focus:outline-none"
                rows={3}
              />

              {error && <p className="text-sm text-red-600">{error}</p>}

              <button
                type="submit"
                disabled={submitting}
                className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light disabled:opacity-50"
              >
                {submitting ? t("submitting") : isPurchase ? t("submitPurchase") : t("submit")}
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
