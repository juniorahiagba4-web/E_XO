"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";

type FaqItem = { question: string; answer: string };

export default function FaqPage() {
  const t = useTranslations("faq");
  const items = t.raw("items") as FaqItem[];
  const [openIndex, setOpenIndex] = useState<number | null>(0);

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-3 text-3xl font-bold text-brand-navy">{t("title")}</h1>
      <p className="mb-10 text-slate-500">{t("intro")}</p>

      <div className="divide-y divide-slate-200 rounded-2xl border border-slate-200">
        {items.map((item, index) => {
          const isOpen = openIndex === index;
          return (
            <div key={item.question}>
              <button
                type="button"
                onClick={() => setOpenIndex(isOpen ? null : index)}
                aria-expanded={isOpen}
                className="flex w-full items-center justify-between gap-4 px-6 py-4 text-left font-medium text-brand-navy"
              >
                {item.question}
                <span className={`shrink-0 text-slate-400 transition-transform ${isOpen ? "rotate-45" : ""}`}>
                  +
                </span>
              </button>
              {isOpen && (
                <p className="px-6 pb-4 text-slate-500">{item.answer}</p>
              )}
            </div>
          );
        })}
      </div>

      <div className="mt-10 text-center">
        <Link
          href="/contact"
          className="inline-block rounded-full border border-brand-navy px-6 py-3 font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white"
        >
          {t("contactCta")}
        </Link>
      </div>
    </div>
  );
}
