export default function Logo({
  withWordmark = true,
  size = 40,
  className = "",
}: {
  withWordmark?: boolean;
  size?: number;
  className?: string;
}) {
  return (
    <span className={`inline-flex flex-col items-center gap-1 ${className}`}>
      <span
        className="inline-block shrink-0 overflow-hidden rounded-lg"
        style={{ width: size, height: size }}
      >
        {/* The source file is a square badge with the wordmark baked in below
            the mark; cropping to its top portion isolates just the emblem,
            which stays legible at header/footer sizes (the baked-in text
            would not). */}
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src="/logo.png"
          alt="La Perle d'Or"
          style={{ width: size, height: size / 0.68, objectFit: "cover", objectPosition: "top" }}
        />
      </span>
      {withWordmark && (
        <span
          className="text-[9px] leading-none tracking-[0.14em] text-white uppercase"
          style={{ fontFamily: "var(--font-archivo-black), sans-serif" }}
        >
          La Perle d&apos;Or
        </span>
      )}
    </span>
  );
}
