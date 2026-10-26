/**
 * Official La Perle d'Or logo, rendered from the vector artwork supplied by the
 * brand (see design/brand/logo-source.pdf). Every variant keeps the emblem and
 * the "LA PERLE D'OR" wordmark together — they are one lockup, never separate
 * pieces — so the file already contains both and only the height is set here.
 *
 * Assets live in /public/brand:
 *   horizontal-dark  emblem + one-line wordmark, gold on white (navy surface)
 *   horizontal-light emblem + one-line wordmark, gold on navy  (light surface)
 *   lockup-dark      emblem above wordmark, gold on white      (navy surface)
 *   lockup-light     emblem above wordmark, gold on navy       (light surface)
 *   mark             emblem only, gold, for tight spots and app icons
 */
type LogoVariant = "horizontal" | "stacked" | "mark";
type LogoSurface = "dark" | "light";

const ASSETS = {
  horizontal: {
    dark: { src: "/brand/logo-horizontal-dark.svg", ratio: 857.514 / 211.345 },
    light: { src: "/brand/logo-horizontal-light.svg", ratio: 857.514 / 211.345 },
  },
  stacked: {
    dark: { src: "/brand/logo-lockup-dark.svg", ratio: 847.509 / 646.643 },
    light: { src: "/brand/logo-lockup-light.svg", ratio: 847.509 / 646.643 },
  },
  mark: {
    dark: { src: "/brand/logo-mark.svg", ratio: 447.219 / 510.669 },
    light: { src: "/brand/logo-mark.svg", ratio: 447.219 / 510.669 },
  },
} as const;

export default function Logo({
  variant = "horizontal",
  surface = "dark",
  className = "h-10",
}: {
  /** Lockup shape: horizontal for bars, stacked for roomy blocks, mark for icons. */
  variant?: LogoVariant;
  /** Colour of the background the logo sits on. */
  surface?: LogoSurface;
  /** Height utilities (e.g. "h-8 md:h-9"); width follows the artwork ratio. */
  className?: string;
}) {
  const { src, ratio } = ASSETS[variant][surface];

  return (
    // eslint-disable-next-line @next/next/no-img-element -- static vector asset, no optimisation needed
    <img
      src={src}
      alt="La Perle d'Or"
      width={Math.round(ratio * 1000) / 10}
      height={100}
      className={`w-auto max-w-none shrink-0 select-none ${className}`}
    />
  );
}
