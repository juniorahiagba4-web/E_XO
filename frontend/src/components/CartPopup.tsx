"use client";

import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import { validatePromoCode, ApiError } from "@/lib/api";
import { computeDiscountPreview } from "@/lib/promo";
import QuantityStepper from "./QuantityStepper";
import Modal from "./Modal";

export default function CartPopup() {
  const t = useTranslations("nav");
  const tCart = useTranslations("quoteForm");
  const locale = useLocale();
  const cart = useCart();
  const [open, setOpen] = useState(false);
  const [promoInput, setPromoInput] = useState("");
  const [promoStatus, setPromoStatus] = useState<"idle" | "checking" | "error">("idle");
  const [promoError, setPromoError] = useState<string | null>(null);

  const isPurchase = cart.orderType === "purchase";
  const count = cart.lines.reduce((sum, l) => sum + l.quantity, 0);
  const lineTotal = (l: (typeof cart.lines)[number]) => {
    const price = isPurchase ? Number(l.item.sale_price ?? 0) : Number(l.item.rental_price_per_day);
    return price * l.quantity;
  };
  const subtotal = cart.lines.reduce((sum, l) => sum + lineTotal(l), 0);
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
      setPromoError(err instanceof ApiError ? err.message : tCart("promoCodeApplying"));
    }
  }

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label={t("cart")}
        className="relative flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-slate-200 transition hover:border-brand-gold hover:text-brand-gold"
      >
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
          <path strokeLinecap="round" strokeLinejoin="round" d="M3 4h2l1.5 12.5A2 2 0 0 0 8.5 18h9a2 2 0 0 0 2-1.7L21 8H6" />
          <circle cx="9.5" cy="21" r="1.3" fill="currentColor" stroke="none" />
          <circle cx="17.5" cy="21" r="1.3" fill="currentColor" stroke="none" />
        </svg>
        {count > 0 && (
          <span className="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-navy px-1 text-[11px] font-semibold text-white">
            {count}
          </span>
        )}
      </button>

      <Modal open={open} onClose={() => setOpen(false)} align="right">
        <div className="flex items-center justify-between border-b border-slate-100 p-5">
          <h2 className="flex items-center gap-2 text-xl font-bold text-brand-navy">
            {tCart("cartTitle")}
            {count > 0 && (
              <span className="flex h-6 min-w-6 items-center justify-center rounded-full bg-brand-navy px-1.5 text-xs font-semibold text-white">
                {count}
              </span>
            )}
          </h2>
          <button
            type="button"
            onClick={() => setOpen(false)}
            aria-label={t("close")}
            className="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:border-brand-navy hover:text-brand-navy"
          >
            ✕
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-4">
          {cart.lines.length === 0 ? (
            <p className="p-4 text-center text-sm text-slate-500">{tCart("emptyCart")}</p>
          ) : (
            <ul className="flex flex-col gap-3">
              {cart.lines.map((line) => {
                const unitPrice = isPurchase
                  ? Number(line.item.sale_price ?? 0)
                  : Number(line.item.rental_price_per_day);
                const lineTotal = unitPrice * line.quantity;
                const max = isPurchase ? line.item.purchasable_quantity : line.item.total_stock;

                return (
                  <li key={line.item.id} className="flex gap-3 rounded-2xl bg-slate-50 p-3">
                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-white">
                      {line.item.image_url ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={line.item.image_url} alt={line.item.name} className="h-full w-full object-cover" />
                      ) : (
                        <div className="flex h-full w-full items-center justify-center text-slate-300">
                          {line.item.name.charAt(0)}
                        </div>
                      )}
                    </div>
                    <div className="flex min-w-0 flex-1 flex-col justify-between">
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <p className="truncate text-sm font-semibold text-brand-navy">{line.item.name}</p>
                          {line.item.category && (
                            <p className="truncate text-xs text-slate-400">{line.item.category.name}</p>
                          )}
                        </div>
                        <button
                          type="button"
                          onClick={() => cart.removeLine(line.item.id)}
                          aria-label={tCart("removeLine")}
                          className="shrink-0 text-slate-300 hover:text-red-500"
                        >
                          ✕
                        </button>
                      </div>
                      <div className="flex items-center justify-between gap-2">
                        <QuantityStepper
                          value={line.quantity}
                          min={1}
                          max={max}
                          onChange={(value) => cart.updateQuantity(line.item.id, value)}
                        />
                        <p className="text-sm font-bold text-brand-navy">
                          {lineTotal.toLocaleString()} XOF
                        </p>
                      </div>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        {cart.lines.length > 0 && (
          <div className="border-t border-slate-100 p-5">
            {cart.promo ? (
              <div className="mb-3 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                <span>{tCart("promoCodeApplied", { code: cart.promo.code })}</span>
                <button type="button" onClick={cart.removePromo} className="font-medium underline">
                  {tCart("promoCodeRemove")}
                </button>
              </div>
            ) : (
              <div className="mb-3">
                <div className="flex gap-2">
                  <input
                    value={promoInput}
                    onChange={(e) => setPromoInput(e.target.value)}
                    placeholder={tCart("promoCodePlaceholder")}
                    className="min-w-0 flex-1 rounded-full border border-slate-300 px-4 py-2 text-sm focus:border-brand-gold focus:outline-none"
                  />
                  <button
                    type="button"
                    onClick={applyPromoCode}
                    disabled={promoStatus === "checking" || !promoInput.trim()}
                    className="shrink-0 rounded-full border border-brand-navy px-4 py-2 text-sm font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white disabled:opacity-40"
                  >
                    {promoStatus === "checking" ? tCart("promoCodeApplying") : tCart("promoCodeApply")}
                  </button>
                </div>
                {promoError && <p className="mt-1.5 text-xs text-red-500">{promoError}</p>}
              </div>
            )}

            <div className="mb-1 flex items-center justify-between text-sm">
              <span className="text-slate-500">{tCart("subtotal")}</span>
              <span className="text-brand-navy">{subtotal.toLocaleString()} XOF</span>
            </div>
            {discount > 0 && (
              <div className="mb-1 flex items-center justify-between text-sm">
                <span className="text-emerald-600">{tCart("discount")}</span>
                <span className="text-emerald-600">-{discount.toLocaleString()} XOF</span>
              </div>
            )}
            <div className="mb-4 flex items-center justify-between">
              <span className="font-medium text-slate-500">{tCart("total")}</span>
              <span className="text-xl font-bold text-brand-navy">{total.toLocaleString()} XOF</span>
            </div>
            <Link
              href="/devis"
              onClick={() => setOpen(false)}
              className="flex items-center justify-center gap-2 rounded-full bg-brand-navy px-4 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-brand-navy-light"
            >
              {tCart("checkout")}
            </Link>
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="mt-2 w-full rounded-full border border-slate-200 px-4 py-3 text-center text-sm font-medium text-brand-navy transition hover:border-brand-navy"
            >
              {tCart("continueShopping")}
            </button>
          </div>
        )}
      </Modal>
    </>
  );
}
