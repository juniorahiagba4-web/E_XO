"use client";

import { useEffect, useRef, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { DayPicker, type DateRange } from "react-day-picker";
import { fr, enUS } from "date-fns/locale";
import { format, differenceInCalendarDays, parseISO } from "date-fns";

function toDate(value: string): Date | undefined {
  return value ? parseISO(value) : undefined;
}

function toIso(date: Date): string {
  return format(date, "yyyy-MM-dd");
}

export default function DateRangePicker({
  startDate,
  endDate,
  onChange,
}: {
  startDate: string;
  endDate: string;
  onChange: (start: string, end: string) => void;
}) {
  const t = useTranslations("product");
  const locale = useLocale();
  const containerRef = useRef<HTMLDivElement>(null);
  const [open, setOpen] = useState(false);
  const [draft, setDraft] = useState<DateRange | undefined>({
    from: toDate(startDate),
    to: toDate(endDate),
  });

  function toggleOpen() {
    // Re-seed the in-progress selection from the committed props each time
    // the popover opens, rather than keeping it continuously synced — the
    // draft only needs to reflect reality while the user is actively picking.
    setDraft({ from: toDate(startDate), to: toDate(endDate) });
    setOpen((v) => !v);
  }

  useEffect(() => {
    if (!open) return;

    function onPointerDown(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(false);
    }

    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  function handleSelect(range: DateRange | undefined) {
    setDraft(range);
    // react-day-picker's range mode reports a single click as a same-day
    // `{from, to}` pair, not `{from, to: undefined}` — so only auto-apply
    // once the two differ (a real second click extending the range).
    // A deliberate one-day selection still works via the Apply button.
    if (range?.from && range?.to && range.to.getTime() !== range.from.getTime()) {
      onChange(toIso(range.from), toIso(range.to));
      setOpen(false);
    }
  }

  function apply() {
    if (draft?.from) {
      onChange(toIso(draft.from), toIso(draft.to ?? draft.from));
    }
    setOpen(false);
  }

  function clear() {
    setDraft(undefined);
    onChange("", "");
  }

  const nights =
    draft?.from && draft?.to ? Math.max(1, differenceInCalendarDays(draft.to, draft.from)) : 0;

  const label =
    startDate && endDate
      ? `${format(toDate(startDate)!, "d MMM", { locale: locale === "fr" ? fr : enUS })} → ${format(
          toDate(endDate)!,
          "d MMM",
          { locale: locale === "fr" ? fr : enUS },
        )}`
      : t("addDates");

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        onClick={toggleOpen}
        className="flex w-full items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-left text-sm transition hover:border-brand-navy"
      >
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" className="shrink-0 text-slate-400" aria-hidden>
          <rect x="3" y="5" width="18" height="16" rx="2" />
          <path strokeLinecap="round" d="M8 3v4M16 3v4M3 10h18" />
        </svg>
        <span className={startDate && endDate ? "font-medium text-brand-navy" : "text-slate-400"}>
          {label}
        </span>
      </button>

      {open && (
        <div className="absolute left-0 top-full z-30 mt-2 w-[calc(100vw-2rem)] max-w-[340px] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:w-[600px] sm:max-w-none">
          <DayPicker
            mode="range"
            locale={locale === "fr" ? fr : enUS}
            selected={draft}
            onSelect={handleSelect}
            numberOfMonths={2}
            disabled={{ before: new Date() }}
            weekStartsOn={1}
            classNames={{
              months: "flex gap-6 [&>*:nth-child(2)]:hidden sm:[&>*:nth-child(2)]:block",
              month: "flex flex-col gap-3",
              month_caption: "flex items-center justify-center h-9 font-semibold text-brand-navy",
              caption_label: "text-sm",
              nav: "flex items-center justify-between absolute inset-x-0 top-0 h-9 px-1",
              button_previous:
                "flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 disabled:opacity-30",
              button_next:
                "flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 disabled:opacity-30",
              chevron: "fill-current",
              month_grid: "border-collapse",
              weekdays: "flex",
              weekday: "w-9 text-center text-[11px] font-medium uppercase text-slate-400",
              weeks: "flex flex-col gap-0.5",
              week: "flex",
              day: "w-9 h-9 text-center align-middle p-0 relative",
              day_button:
                "h-9 w-9 rounded-full text-sm font-medium text-slate-700 transition hover:bg-brand-gold/20",
              range_start: "[&>button]:bg-brand-navy [&>button]:text-white [&>button]:hover:bg-brand-navy rounded-l-full bg-brand-gold/15",
              range_end: "[&>button]:bg-brand-navy [&>button]:text-white [&>button]:hover:bg-brand-navy rounded-r-full bg-brand-gold/15",
              range_middle: "bg-brand-gold/15 [&>button]:rounded-none [&>button]:bg-transparent [&>button]:hover:bg-transparent",
              selected: "",
              today: "font-bold",
              disabled: "text-slate-300 hover:bg-transparent cursor-not-allowed",
              outside: "invisible",
            }}
          />

          <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
            <span className="text-xs text-slate-500">
              {nights > 0 ? t("nights", { count: nights }) : ""}
            </span>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={clear}
                className="rounded-full px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100"
              >
                {t("clearDates")}
              </button>
              <button
                type="button"
                onClick={apply}
                disabled={!draft?.from}
                className="rounded-full bg-brand-navy px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-navy-light disabled:opacity-40"
              >
                {t("applyDates")}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
