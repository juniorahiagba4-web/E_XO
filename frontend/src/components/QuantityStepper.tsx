"use client";

import { useState } from "react";

/**
 * Quantity picker: − / + buttons for small changes, and a field in the middle
 * where a large quantity (500 chairs...) can be typed directly. The typed
 * value is applied when the field loses focus or on Enter, clamped to the
 * allowed range.
 */
export default function QuantityStepper({
  value,
  onChange,
  min = 1,
  max,
}: {
  value: number;
  onChange: (value: number) => void;
  min?: number;
  max?: number;
}) {
  const [draft, setDraft] = useState(String(value));
  const [shownValue, setShownValue] = useState(value);

  // The value changed from outside (− / + buttons, cart): show it in the field.
  if (value !== shownValue) {
    setShownValue(value);
    setDraft(String(value));
  }

  function clamp(next: number) {
    let result = Math.round(next);
    if (min !== undefined) result = Math.max(min, result);
    if (max !== undefined) result = Math.min(max, result);
    return result;
  }

  function commit() {
    const parsed = parseInt(draft, 10);
    const next = Number.isNaN(parsed) ? value : clamp(parsed);
    setDraft(String(next));
    if (next !== value) onChange(next);
  }

  const buttonClass =
    "flex h-7 w-7 shrink-0 items-center justify-center text-sm font-medium text-slate-600 transition hover:bg-brand-navy-light hover:text-white disabled:pointer-events-none disabled:opacity-30";

  return (
    <div className="inline-flex w-fit self-start items-center overflow-hidden rounded-full border border-slate-300">
      <button
        type="button"
        onClick={() => onChange(clamp(value - 1))}
        disabled={value <= min}
        aria-label="Diminuer la quantité"
        className={buttonClass}
      >
        −
      </button>
      <input
        type="text"
        inputMode="numeric"
        pattern="[0-9]*"
        value={draft}
        onChange={(e) => setDraft(e.target.value.replace(/\D/g, "").slice(0, 6))}
        onBlur={commit}
        onFocus={(e) => e.target.select()}
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            commit();
            e.currentTarget.blur();
          } else if (e.key === "ArrowUp") {
            e.preventDefault();
            onChange(clamp(value + 1));
          } else if (e.key === "ArrowDown") {
            e.preventDefault();
            onChange(clamp(value - 1));
          }
        }}
        aria-label="Quantité"
        style={{ width: `${Math.max(2, draft.length) + 1.5}ch` }}
        className="h-7 bg-transparent text-center text-xs font-semibold text-brand-navy outline-none focus:bg-brand-gold/15"
      />
      <button
        type="button"
        onClick={() => onChange(clamp(value + 1))}
        disabled={max !== undefined && value >= max}
        aria-label="Augmenter la quantité"
        className={buttonClass}
      >
        +
      </button>
    </div>
  );
}
