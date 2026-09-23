"use client";

import { useState } from "react";
import FavoriteHeart from "./FavoriteHeart";
import type { Item } from "@/lib/types";

export default function ItemGallery({ item }: { item: Item }) {
  const images = item.gallery.length > 0 ? item.gallery : item.image_url ? [item.image_url] : [];
  const [active, setActive] = useState(0);

  return (
    <div>
      <div className="relative aspect-square overflow-hidden rounded-2xl bg-slate-100">
        {images.length > 0 ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={images[active]} alt={item.name} className="h-full w-full object-cover" />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-4xl text-slate-300">
            {item.name.charAt(0)}
          </div>
        )}
        <div className="absolute right-3 top-3">
          <FavoriteHeart item={item} size="md" />
        </div>
      </div>

      {images.length > 1 && (
        <div className="mt-3 flex gap-2 overflow-x-auto">
          {images.map((src, index) => (
            <button
              key={src}
              type="button"
              onClick={() => setActive(index)}
              className={`h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition ${
                index === active ? "border-brand-navy" : "border-transparent opacity-70 hover:opacity-100"
              }`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={src} alt="" className="h-full w-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
