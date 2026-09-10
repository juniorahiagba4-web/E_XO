import { buildWhatsappLink } from "@/lib/config";

export default function WhatsappButton({
  message,
  label,
}: {
  message: string;
  label: string;
}) {
  return (
    <a
      href={buildWhatsappLink(message)}
      target="_blank"
      rel="noopener noreferrer"
      className="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-5 py-3 font-medium text-white shadow-sm transition hover:brightness-95"
    >
      <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden>
        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.85.5 3.58 1.38 5.07L2 22l5.2-1.47a9.86 9.86 0 0 0 4.84 1.24h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2Zm5.86 14.06c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.11.11-1.79-.11-.41-.13-.94-.3-1.62-.6-2.86-1.24-4.72-4.1-4.86-4.29-.14-.19-1.16-1.55-1.16-2.96 0-1.4.73-2.09 1-2.38.24-.26.53-.32.71-.32.18 0 .35 0 .5.01.16 0 .38-.06.6.46.24.57.8 1.98.87 2.12.07.14.11.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.71 1.17 1.53 1.9 1.05.94 1.94 1.23 2.22 1.37.28.14.44.12.6-.07.16-.19.68-.79.87-1.06.18-.28.36-.23.6-.14.24.09 1.55.73 1.82.86.27.14.45.2.51.32.07.12.07.66-.17 1.34Z" />
      </svg>
      {label}
    </a>
  );
}
