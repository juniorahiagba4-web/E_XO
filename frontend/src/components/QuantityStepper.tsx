"use client";

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
  function clamp(next: number) {
    let result = next;
    if (min !== undefined) result = Math.max(min, result);
    if (max !== undefined) result = Math.min(max, result);
    return result;
  }

  return (
    <div className="inline-flex items-center overflow-hidden rounded-full border border-slate-300">
      <button
        type="button"
        onClick={() => onChange(clamp(value - 1))}
        disabled={value <= min}
        aria-label="Diminuer la quantité"
        className="flex h-7 w-7 items-center justify-center text-sm font-medium text-slate-600 transition hover:bg-brand-navy hover:text-white disabled:pointer-events-none disabled:opacity-30"
      >
        −
      </button>
      <span className="w-6 text-center text-xs font-semibold text-brand-navy">{value}</span>
      <button
        type="button"
        onClick={() => onChange(clamp(value + 1))}
        disabled={max !== undefined && value >= max}
        aria-label="Augmenter la quantité"
        className="flex h-7 w-7 items-center justify-center text-sm font-medium text-slate-600 transition hover:bg-brand-navy hover:text-white disabled:pointer-events-none disabled:opacity-30"
      >
        +
      </button>
    </div>
  );
}
