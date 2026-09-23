"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import RatingStars from "./RatingStars";
import type { Item } from "@/lib/types";

type Tab = "description" | "specifications" | "reviews";

export default function ItemTabs({ item }: { item: Item }) {
  const t = useTranslations("product");
  const [tab, setTab] = useState<Tab>("description");

  const tabs: { key: Tab; label: string }[] = [
    { key: "description", label: t("tabDescription") },
    { key: "specifications", label: t("tabSpecifications") },
    { key: "reviews", label: t("tabReviews") },
  ];

  return (
    <div className="mt-12">
      <div className="flex gap-6 border-b border-slate-200">
        {tabs.map(({ key, label }) => (
          <button
            key={key}
            type="button"
            onClick={() => setTab(key)}
            className={`-mb-px border-b-2 px-1 py-3 text-sm font-medium transition ${
              tab === key
                ? "border-brand-navy text-brand-navy"
                : "border-transparent text-slate-500 hover:text-brand-navy"
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      <div className="py-6">
        {tab === "description" &&
          (item.description ? (
            <p className="whitespace-pre-line text-slate-600">{item.description}</p>
          ) : (
            <p className="text-slate-400">{t("noDescription")}</p>
          ))}

        {tab === "specifications" &&
          (item.specifications.length > 0 ? (
            <dl className="grid gap-x-8 sm:grid-cols-2">
              {item.specifications.map((spec) => (
                <div
                  key={spec.label}
                  className="flex justify-between border-b border-slate-100 py-2 text-sm"
                >
                  <dt className="text-slate-500">{spec.label}</dt>
                  <dd className="font-medium text-brand-navy">{spec.value}</dd>
                </div>
              ))}
            </dl>
          ) : (
            <p className="text-slate-400">{t("noSpecifications")}</p>
          ))}

        {tab === "reviews" &&
          (item.reviews.length > 0 ? (
            <div className="flex flex-col gap-5">
              {item.reviews.map((review) => (
                <div key={review.id} className="border-b border-slate-100 pb-4">
                  <div className="flex items-center justify-between">
                    <span className="font-medium text-brand-navy">{review.author_name}</span>
                    <RatingStars rating={Number(review.rating)} />
                  </div>
                  {review.comment && (
                    <p className="mt-1 text-sm text-slate-600">{review.comment}</p>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <p className="text-slate-400">{t("noReviews")}</p>
          ))}
      </div>
    </div>
  );
}
