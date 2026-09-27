"use client";

import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import QuantityStepper from "./QuantityStepper";
import Modal from "./Modal";

export default function CartPopup() {
  const t = useTranslations("nav");
  const tCart = useTranslations("quoteForm");
  const cart = useCart();

  const isPurchase = cart.orderType === "purchase";
  const count = cart.lines.reduce((sum, l) => sum + l.quantity, 0);
  const subtotal = cart.lines.reduce((sum, l) => {
    const price = isPurchase ? Number(l.item.sale_price ?? 0) : Number(l.item.rental_price_per_day);
    return sum + price * l.quantity;
  }, 0);

  return (
    <>
      <button
        type="button"
        onClick={() => cart.openCart()}
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

      <Modal open={cart.isOpen} onClose={() => cart.closeCart()} align="right">
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
            onClick={() => cart.closeCart()}
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
            <div className="mb-4 flex items-center justify-between">
              <span className="text-slate-500">{tCart("total")}</span>
              <span className="text-xl font-bold text-brand-navy">{subtotal.toLocaleString()} XOF</span>
            </div>
            <Link
              href="/devis"
              onClick={() => cart.closeCart()}
              className="flex items-center justify-center gap-2 rounded-full bg-brand-navy px-4 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-brand-navy-light"
            >
              {tCart("checkout")}
            </Link>
            <button
              type="button"
              onClick={() => cart.closeCart()}
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
