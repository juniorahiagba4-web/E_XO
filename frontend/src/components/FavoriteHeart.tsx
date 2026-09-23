"use client";

import { useAuth } from "@/lib/auth-context";
import { useFavorites } from "@/lib/favorites-context";
import { useRouter } from "@/i18n/navigation";
import type { Item } from "@/lib/types";

export default function FavoriteHeart({
  item,
  size = "sm",
}: {
  item: Item;
  size?: "sm" | "md";
}) {
  const { user } = useAuth();
  const { isFavorited, toggleFavorite } = useFavorites();
  const router = useRouter();

  const favorited = user ? isFavorited(item.id) : false;
  const dimension = size === "md" ? "h-10 w-10" : "h-8 w-8";
  const iconSize = size === "md" ? 20 : 16;

  function handleClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (!user) {
      router.push(`/connexion?redirect=/catalogue/${item.slug}`);
      return;
    }
    toggleFavorite(item);
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      aria-pressed={favorited}
      aria-label="Favori"
      className={`flex ${dimension} items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:scale-105`}
    >
      <svg
        viewBox="0 0 24 24"
        width={iconSize}
        height={iconSize}
        fill={favorited ? "#f43f5e" : "none"}
        stroke={favorited ? "#f43f5e" : "currentColor"}
        strokeWidth="1.8"
        aria-hidden
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M12 20.5s-7.5-4.6-9.6-9.1C1 8.1 2.4 4.9 5.6 4.1c2-.5 3.9.3 5 1.9l1.4 2 1.4-2c1.1-1.6 3-2.4 5-1.9 3.2.8 4.6 4 3.2 7.3-2.1 4.5-9.6 9.1-9.6 9.1Z"
        />
      </svg>
    </button>
  );
}
