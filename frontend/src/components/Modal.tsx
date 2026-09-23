"use client";

import { useEffect, type ReactNode } from "react";

export default function Modal({
  open,
  onClose,
  children,
  align = "center",
  className = "",
}: {
  open: boolean;
  onClose: () => void;
  children: ReactNode;
  align?: "center" | "right";
  className?: string;
}) {
  useEffect(() => {
    if (!open) return;

    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") onClose();
    }

    document.addEventListener("keydown", onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      className={`fixed inset-0 z-50 flex p-4 ${align === "right" ? "justify-end" : "items-center justify-center"}`}
      role="dialog"
      aria-modal="true"
    >
      <div
        className="absolute inset-0 bg-brand-navy/40 backdrop-blur-sm"
        style={{ animation: "backdrop-in .15s ease-out" }}
        onClick={onClose}
      />
      <div
        className={`relative flex max-h-full w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ${
          align === "right" ? "max-w-sm my-4 mr-0" : "max-w-lg"
        } ${className}`}
        style={{ animation: "modal-in .18s ease-out" }}
      >
        {children}
      </div>
    </div>
  );
}
