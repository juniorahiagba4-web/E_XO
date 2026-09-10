"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";
import { getItemAvailability, ApiError } from "@/lib/api";
import { useCart } from "@/lib/cart-context";
import type { Item } from "@/lib/types";

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

  const [startDate, setStartDate] = useState(cart.eventStartDate);
  const [endDate, setEndDate] = useState(cart.eventEndDate);
  const [quantity, setQuantity] = useState(item.min_rental_quantity || 1);
  const [status, setStatus] = useState<"idle" | "checking" | "checked" | "error">("idle");
  const [availableQuantity, setAvailableQuantity] = useState<number | null>(null);

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

  function addToQuote() {
    cart.setEventDates(startDate, endDate);
    cart.addLine(item, quantity);
    router.push("/devis");
  }

  const isAvailable = availableQuantity !== null && availableQuantity >= quantity;

  return (
    <div className="rounded-2xl border border-slate-200 p-6">
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

      <label className="mt-4 flex flex-col gap-1 text-sm">
        {t("quantity")}
        <input
          type="number"
          min={item.min_rental_quantity || 1}
          max={item.total_stock}
          value={quantity}
          onChange={(e) => {
            setQuantity(Number(e.target.value));
            setStatus("idle");
          }}
          className="w-32 rounded-lg border border-slate-300 px-3 py-2"
        />
      </label>

      <button
        type="button"
        disabled={!canCheck || status === "checking"}
        onClick={checkAvailability}
        className="mt-4 w-full rounded-full bg-slate-900 px-4 py-2 font-medium text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {status === "checking" ? t("checking") : t("checkAvailability")}
      </button>

      <div className="mt-3 min-h-6 text-sm">
        {status === "idle" && !canCheck && (
          <span className="text-slate-400">{t("selectDates")}</span>
        )}
        {status === "checked" && isAvailable && (
          <span className="text-emerald-600">
            {t("available", { count: availableQuantity })}
          </span>
        )}
        {status === "checked" && !isAvailable && (
          <span className="text-red-600">
            {t("unavailable", { count: availableQuantity ?? 0 })}
          </span>
        )}
        {status === "error" && (
          <span className="text-red-600">{t("checkAvailability")} ✕</span>
        )}
      </div>

      <button
        type="button"
        disabled={status !== "checked" || !isAvailable}
        onClick={addToQuote}
        className="mt-4 w-full rounded-full border border-slate-900 px-4 py-2 font-medium text-slate-900 transition hover:bg-slate-900 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
      >
        {t("addToQuote")}
      </button>
    </div>
  );
}
