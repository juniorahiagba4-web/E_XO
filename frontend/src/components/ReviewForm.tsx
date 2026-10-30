"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getReviewRequest, submitReviews, ApiError } from "@/lib/api";
import type { ReviewRequest } from "@/lib/types";

type Draft = { rating: number; comment: string };

function StarPicker({
  value,
  onChange,
  label,
}: {
  value: number;
  onChange: (value: number) => void;
  label: string;
}) {
  const [hover, setHover] = useState(0);
  const shown = hover || value;

  return (
    <div role="radiogroup" aria-label={label} className="flex gap-1" onMouseLeave={() => setHover(0)}>
      {[1, 2, 3, 4, 5].map((star) => (
        <button
          key={star}
          type="button"
          role="radio"
          aria-checked={value === star}
          aria-label={`${star} / 5`}
          onClick={() => onChange(star)}
          onMouseEnter={() => setHover(star)}
          className={`text-3xl leading-none ${star <= shown ? "text-brand-gold" : "text-slate-200"}`}
        >
          ★
        </button>
      ))}
    </div>
  );
}

export default function ReviewForm({ reference, reviewToken }: { reference: string; reviewToken: string }) {
  const t = useTranslations("reviewForm");
  const locale = useLocale();

  const [request, setRequest] = useState<ReviewRequest | null>(null);
  const [loadFailed, setLoadFailed] = useState(false);
  const [drafts, setDrafts] = useState<Record<number, Draft>>({});
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  useEffect(() => {
    if (!reviewToken) return;
    getReviewRequest(locale, reference, reviewToken)
      .then(setRequest)
      .catch(() => setLoadFailed(true));
  }, [locale, reference, reviewToken]);

  function updateDraft(itemId: number, patch: Partial<Draft>) {
    setDrafts((current) => ({
      ...current,
      [itemId]: { ...(current[itemId] ?? { rating: 0, comment: "" }), ...patch },
    }));
  }

  const toReview = request?.items.filter((item) => !item.reviewed) ?? [];
  const rated = Object.entries(drafts).filter(([, draft]) => draft.rating > 0);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (rated.length === 0) return;
    setSubmitting(true);
    setError(null);
    try {
      await submitReviews(
        locale,
        reference,
        reviewToken,
        rated.map(([itemId, draft]) => ({
          item_id: Number(itemId),
          rating: draft.rating,
          comment: draft.comment.trim() || undefined,
        })),
      );
      setDone(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("error"));
    } finally {
      setSubmitting(false);
    }
  }

  if (loadFailed || !reviewToken) {
    return (
      <div className="text-center">
        <h1 className="mb-3 text-2xl font-bold text-brand-navy">{t("invalidTitle")}</h1>
        <p className="mb-6 text-slate-500">{t("invalidText")}</p>
        <Link href="/" className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white">
          {t("backHome")}
        </Link>
      </div>
    );
  }

  if (!request) {
    return <p className="text-center text-slate-400">{t("loading")}</p>;
  }

  if (done || toReview.length === 0) {
    return (
      <div className="text-center">
        <p className="mb-4 text-5xl text-brand-gold" aria-hidden>
          ★
        </p>
        <h1 className="mb-3 text-2xl font-bold text-brand-navy">{t("thanksTitle")}</h1>
        <p className="mb-6 text-slate-500">{done ? t("thanksText") : t("alreadyDone")}</p>
        <Link href="/catalogue" className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white">
          {t("backCatalogue")}
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit}>
      <h1 className="mb-2 text-3xl font-bold text-brand-navy">
        {request.first_name ? t("greeting", { name: request.first_name }) : t("title")}
      </h1>
      <p className="mb-8 text-slate-500">{t("intro")}</p>

      <div className="flex flex-col gap-4">
        {toReview.map((item) => {
          const draft = drafts[item.item_id];
          return (
            <div key={item.item_id} className="flex gap-4 rounded-2xl border border-slate-200 p-4">
              <div className="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                {item.image_url && (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.image_url} alt="" className="h-full w-full object-cover" />
                )}
              </div>
              <div className="flex min-w-0 flex-1 flex-col gap-2">
                <p className="font-medium text-brand-navy">{item.name}</p>
                <StarPicker
                  value={draft?.rating ?? 0}
                  onChange={(rating) => updateDraft(item.item_id, { rating })}
                  label={t("ratingLabel", { name: item.name })}
                />
                {draft && draft.rating > 0 && (
                  <textarea
                    value={draft.comment}
                    onChange={(e) => updateDraft(item.item_id, { comment: e.target.value })}
                    placeholder={t("commentPlaceholder")}
                    maxLength={1000}
                    rows={3}
                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  />
                )}
              </div>
            </div>
          );
        })}
      </div>

      <p className="mt-6 text-xs text-slate-400">{t("moderationNote")}</p>

      {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

      <button
        type="submit"
        disabled={submitting || rated.length === 0}
        className="mt-4 w-full rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light disabled:cursor-not-allowed disabled:opacity-50"
      >
        {submitting ? t("submitting") : t("submit", { count: rated.length })}
      </button>
    </form>
  );
}
