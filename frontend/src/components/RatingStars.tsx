const STAR = "★";

export default function RatingStars({
  rating,
  count,
  size = "sm",
}: {
  rating: number;
  count?: number;
  size?: "sm" | "md";
}) {
  const rounded = Math.round(rating * 2) / 2;
  const textSize = size === "md" ? "text-base" : "text-xs";

  return (
    <div className={`flex items-center gap-1 ${textSize}`}>
      <span className="text-brand-gold" aria-hidden>
        {Array.from({ length: 5 }, (_, i) => {
          const filled = i + 1 <= rounded;
          const half = !filled && i + 0.5 === rounded;
          return (
            <span key={i} className={filled || half ? "" : "text-slate-200"}>
              {STAR}
            </span>
          );
        })}
      </span>
      <span className="text-slate-500">
        {rating.toFixed(1)}
        {typeof count === "number" && ` (${count})`}
      </span>
    </div>
  );
}
