"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";
import { getItemAvailability, ApiError } from "@/lib/api";
import { useCart } from "@/lib/cart-context";
import { useAuthGate } from "@/lib/auth-gate-context";
import QuantityStepper from "./QuantityStepper";
import type { Item, OrderType } from "@/lib/types";

export default function AvailabilityAndQuote({
  item,
  locale,
}: {
  item: Item;
  locale: string;
}) {
  const t = useTranslations("product");
  const router = useRouter();
  const cart = useCart();
  const { guard } = useAuthGate();

  const canSell = Boolean(item.sale_price) && (item.purchasable_quantity ?? 0) > 0;
  const [mode, setMode] = useState<OrderType>("rental");

  const [startDate, setStartDate] = useState(cart.eventStartDate);
  const [endDate, setEndDate] = useState(cart.eventEndDate);
  const [quantity, setQuantity] = useState(item.min_rental_quantity || 1);
  const [status, setStatus] = useState<"idle" | "checking" | "checked" | "error">("idle");
  const [availableQuantity, setAvailableQuantity] = useState<number | null>(null);
  const [justAdded, setJustAdded] = useState(false);

  const canCheck = Boolean(startDate && endDate);

  async function checkAvailability() {
    if (!canCheck) return;
    setStatus("checking");
    try {
      const res = await getItemAvailability(locale, item.id, startDate, endDate);
      setAvailableQuantity(res.available_quantity);
      setStatus("checked");
    } catch (err) {
      setStatus("error");
      if (err instanceof ApiError) {
        console.error(err.message);
      }
    }
  }

  const isRentalAvailable = mode === "rental" && availableQuantity !== null && availableQuantity >= quantity;
  const isPurchaseAvailable = mode === "purchase" && quantity <= (item.purchasable_quantity ?? 0);
  const canAdd = mode === "rental" ? status === "checked" && isRentalAvailable : isPurchaseAvailable;

  function addToCart() {
    if (!canAdd) return;
    guard(() => {
      if (mode === "rental") cart.setEventDates(startDate, endDate);
      cart.addLine(item, quantity, mode);
      setJustAdded(true);
      window.setTimeout(() => setJustAdded(false), 1500);
    });
  }

  function orderNow() {
    if (!canAdd) return;
    guard(() => {
      if (mode === "rental") cart.setEventDates(startDate, endDate);
      cart.addLine(item, quantity, mode);
      router.push("/devis");
    });
  }

  return (
    <div className="rounded-2xl border border-slate-200 p-6">
      {canSell && (
        <div className="mb-5 inline-flex rounded-full border border-slate-200 p-1 text-sm">
          <button
            type="button"
            onClick={() => setMode("rental")}
            className={`rounded-full px-4 py-1.5 font-medium transition ${
              mode === "rental" ? "bg-brand-navy text-white" : "text-slate-600"
            }`}
          >
            {t("modeRental")}
          </button>
          <button
            type="button"
            onClick={() => setMode("purchase")}
            className={`rounded-full px-4 py-1.5 font-medium transition ${
              mode === "purchase" ? "bg-brand-navy text-white" : "text-slate-600"
            }`}
          >
            {t("modePurchase")}
          </button>
        </div>
      )}

      {mode === "rental" ? (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="flex flex-col gap-1 text-sm">
              {t("startDate")}
              <input
                type="date"
                value={startDate}
                onChange={(e) => {
                  setStartDate(e.target.value);
                  setStatus("idle");
                }}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
            </label>
            <label className="flex flex-col gap-1 text-sm">
              {t("endDate")}
              <input
                type="date"
                value={endDate}
                min={startDate || undefined}
                onChange={(e) => {
                  setEndDate(e.target.value);
                  setStatus("idle");
                }}
                className="rounded-lg border border-slate-300 px-3 py-2"
              />
            </label>
          </div>

          <div className="mt-4 flex flex-col gap-1 text-sm">
            {t("quantity")}
            <QuantityStepper
              value={quantity}
              min={item.min_rental_quantity || 1}
              max={item.total_stock}
              onChange={(value) => {
                setQuantity(value);
                setStatus("idle");
              }}
            />
          </div>

          <button
            type="button"
            disabled={!canCheck || status === "checking"}
            onClick={checkAvailability}
            className="mt-4 w-full rounded-full bg-brand-navy px-4 py-2 font-medium text-white transition hover:bg-brand-navy-light disabled:cursor-not-allowed disabled:opacity-50"
          >
            {status === "checking" ? t("checking") : t("checkAvailability")}
          </button>

          <div className="mt-3 min-h-6 text-sm">
            {status === "idle" && !canCheck && (
              <span className="text-slate-400">{t("selectDates")}</span>
            )}
            {status === "checked" && isRentalAvailable && (
              <span className="text-emerald-600">
                {t("available", { count: availableQuantity })}
              </span>
            )}
            {status === "checked" && !isRentalAvailable && (
              <span className="text-red-600">
                {t("unavailable", { count: availableQuantity ?? 0 })}
              </span>
            )}
            {status === "error" && (
              <span className="text-red-600">{t("checkAvailability")} ✕</span>
            )}
          </div>
        </>
      ) : (
        <>
          <p className="text-sm text-slate-500">
            {t("purchaseAvailable", { count: item.purchasable_quantity ?? 0 })}
          </p>
          <div className="mt-4 flex flex-col gap-1 text-sm">
            {t("quantity")}
            <QuantityStepper
              value={quantity}
              min={1}
              max={item.purchasable_quantity ?? 1}
              onChange={setQuantity}
            />
          </div>
          {!isPurchaseAvailable && (
            <p className="mt-2 text-sm text-red-600">{t("purchaseUnavailable")}</p>
          )}
        </>
      )}

      <div className="mt-4 flex flex-col gap-2 sm:flex-row">
        <button
          type="button"
          disabled={!canAdd}
          onClick={addToCart}
          className="flex-1 rounded-full border border-brand-navy px-4 py-2 font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
        >
          {justAdded ? t("added") : t("addToCart")}
        </button>
        <button
          type="button"
          disabled={!canAdd}
          onClick={orderNow}
          className="flex-1 rounded-full bg-brand-gold px-4 py-2 font-medium text-white transition hover:bg-brand-gold-dark disabled:cursor-not-allowed disabled:opacity-40"
        >
          {mode === "rental" ? t("rentNow") : t("buyNow")}
        </button>
      </div>
    </div>
  );
}
