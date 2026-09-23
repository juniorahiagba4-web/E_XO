import type { ReactNode } from "react";

export default function ErrorState({
  code,
  title,
  message,
  actions,
}: {
  code?: string;
  title: string;
  message: string;
  actions?: ReactNode;
}) {
  return (
    <div className="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
      {code && (
        <span
          className="text-6xl text-brand-gold"
          style={{ fontFamily: "var(--font-archivo-black), sans-serif" }}
        >
          {code}
        </span>
      )}
      <h1 className="mt-4 text-2xl font-bold text-brand-navy">{title}</h1>
      <p className="mt-2 text-slate-500">{message}</p>
      {actions && <div className="mt-8 flex flex-wrap justify-center gap-3">{actions}</div>}
    </div>
  );
}
